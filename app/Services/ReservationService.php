<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\LibraryCopy;
use App\Models\Reservation;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ReservationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function create(
        User $member,
        Collection $collection,
        ?CarbonInterface $reservedAt = null,
        ?string $notes = null
    ): Reservation {
        return DB::transaction(function () use ($member, $collection, $reservedAt, $notes) {
            $member = User::whereKey($member->id)
                ->lockForUpdate()
                ->firstOrFail();

            $collection = Collection::whereKey($collection->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $member->hasRole('member')) {
                throw new RuntimeException('Only member users can create reservations.');
            }

            if (! $member->hasActiveMembership()) {
                throw new RuntimeException('Member does not have active membership.');
            }

            $hasUnpaidFine = $member->borrowings()
                ->whereColumn('fine_amount', '>', 'fine_paid_amount')
                ->exists();

            if ($hasUnpaidFine) {
                throw new RuntimeException('Member has unpaid fine.');
            }

            if ($collection->unit_type !== 'library') {
                throw new RuntimeException('Only library collections can be reserved.');
            }

            if ($collection->publication_status !== 'published') {
                throw new RuntimeException('Only published collections can be reserved.');
            }

            if (! in_array($collection->visibility, ['public', 'member'], true)) {
                throw new RuntimeException('Collection is not available for member reservation.');
            }

            $hasActiveReservation = Reservation::where('member_user_id', $member->id)
                ->where('collection_id', $collection->id)
                ->whereIn('status', ['active', 'notified'])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveReservation) {
                throw new RuntimeException('Member already has active reservation for this collection.');
            }

            $queuePosition = $this->nextQueuePosition($collection->id);
            $reservedAt = $reservedAt ?? now();

            $reservation = Reservation::create([
                'ulid' => (string) Str::ulid(),
                'code' => $this->generateReservationCode(),
                'member_user_id' => $member->id,
                'collection_id' => $collection->id,
                'library_copy_id' => null,
                'queue_position' => $queuePosition,
                'status' => 'active',
                'reserved_at' => $reservedAt,
                'expires_at' => null,
                'notified_at' => null,
                'fulfilled_at' => null,
            ]);

            $this->auditLogService->record(
                module: 'circulation',
                action: 'reserve',
                event: 'reservation.created',
                actor: $member,
                auditable: $reservation,
                oldValues: null,
                newValues: [
                    'code' => $reservation->code,
                    'member_user_id' => $member->id,
                    'collection_id' => $collection->id,
                    'queue_position' => $queuePosition,
                    'status' => 'active',
                ],
                metadata: [
                    'record_code' => $collection->record_code,
                    'notes' => $notes,
                ]
            );

            return $reservation->fresh(['collection.primaryAsset', 'libraryCopy']);
        });
    }

    public function cancel(
        Reservation $reservation,
        User $actor,
        ?string $reason = null
    ): Reservation {
        return DB::transaction(function () use ($reservation, $actor, $reason) {
            $reservation = Reservation::whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($reservation->status, ['active', 'notified'], true)) {
                throw new RuntimeException('Only active or notified reservations can be cancelled.');
            }

            if ($actor->hasRole('member') && (int) $reservation->member_user_id !== (int) $actor->id) {
                throw new RuntimeException('Member can only cancel own reservation.');
            }

            $oldValues = $reservation->toArray();
            $oldQueuePosition = $reservation->queue_position;
            $collectionId = $reservation->collection_id;

            $copy = null;

            if ($reservation->library_copy_id) {
                $copy = LibraryCopy::whereKey($reservation->library_copy_id)
                    ->lockForUpdate()
                    ->first();

                if ($copy && $copy->status === 'reserved') {
                    $copy->update([
                        'status' => 'available',
                    ]);
                }
            }

            $reservation->update([
                'status' => 'cancelled',
            ]);

            $this->reorderQueueAfterLeaving($collectionId, $oldQueuePosition);

            $fresh = $reservation->fresh(['collection.primaryAsset', 'libraryCopy']);

            $this->auditLogService->record(
                module: 'circulation',
                action: 'reservation_cancel',
                event: 'reservation.cancelled',
                actor: $actor,
                auditable: $fresh,
                oldValues: [
                    'status' => $oldValues['status'],
                    'queue_position' => $oldValues['queue_position'],
                    'library_copy_id' => $oldValues['library_copy_id'],
                    'copy_status' => $copy?->status,
                ],
                newValues: [
                    'status' => 'cancelled',
                    'queue_position' => $fresh->queue_position,
                    'library_copy_id' => $fresh->library_copy_id,
                    'copy_status' => $copy?->fresh()?->status,
                ],
                metadata: [
                    'reason' => $reason,
                    'reservation_code' => $fresh->code,
                ]
            );

            return $fresh;
        });
    }

    public function allocateCopy(
        Reservation $reservation,
        LibraryCopy $copy,
        User $actor,
        ?CarbonInterface $expiresAt = null
    ): Reservation {
        return DB::transaction(function () use ($reservation, $copy, $actor, $expiresAt) {
            $reservation = Reservation::whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $copy = LibraryCopy::whereKey($copy->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($reservation->status, ['active', 'notified'], true)) {
                throw new RuntimeException('Only active or notified reservations can be allocated.');
            }

            if ((int) $copy->collection_id !== (int) $reservation->collection_id) {
                throw new RuntimeException('Copy does not belong to reserved collection.');
            }

            if ($copy->status !== 'available') {
                throw new RuntimeException('Only available copy can be allocated.');
            }

            if ($reservation->library_copy_id && (int) $reservation->library_copy_id !== (int) $copy->id) {
                $oldCopy = LibraryCopy::whereKey($reservation->library_copy_id)
                    ->lockForUpdate()
                    ->first();

                if ($oldCopy && $oldCopy->status === 'reserved') {
                    $oldCopy->update([
                        'status' => 'available',
                    ]);
                }
            }

            $oldValues = $reservation->toArray();

            $pickupDays = $this->reservationPickupDays();
            $expiresAt = $expiresAt ?? now()->addDays($pickupDays);

            $copy->update([
                'status' => 'reserved',
            ]);

            $reservation->update([
                'library_copy_id' => $copy->id,
                'status' => 'notified',
                'notified_at' => now(),
                'expires_at' => $expiresAt,
            ]);

            $fresh = $reservation->fresh(['collection.primaryAsset', 'libraryCopy']);

            $this->auditLogService->record(
                module: 'circulation',
                action: 'reservation_allocate_copy',
                event: 'reservation.copy_allocated',
                actor: $actor,
                auditable: $fresh,
                oldValues: [
                    'status' => $oldValues['status'],
                    'library_copy_id' => $oldValues['library_copy_id'],
                    'notified_at' => $oldValues['notified_at'],
                    'expires_at' => $oldValues['expires_at'],
                    'copy_status' => 'available',
                ],
                newValues: [
                    'status' => 'notified',
                    'library_copy_id' => $copy->id,
                    'notified_at' => $fresh->notified_at,
                    'expires_at' => $fresh->expires_at,
                    'copy_status' => $copy->fresh()->status,
                ],
                metadata: [
                    'reservation_code' => $fresh->code,
                    'barcode' => $copy->barcode,
                ]
            );

            return $fresh;
        });
    }

    public function changeStatus(
        Reservation $reservation,
        string $status,
        User $actor,
        ?string $reason = null
    ): Reservation {
        return DB::transaction(function () use ($reservation, $status, $actor, $reason) {
            $allowed = ['active', 'notified', 'fulfilled', 'cancelled', 'expired'];

            if (! in_array($status, $allowed, true)) {
                throw new RuntimeException('Invalid reservation status.');
            }

            $reservation = Reservation::whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservation->status === $status) {
                return $reservation->fresh(['collection.primaryAsset', 'libraryCopy']);
            }

            $oldValues = $reservation->toArray();
            $oldStatus = $reservation->status;
            $oldQueuePosition = $reservation->queue_position;
            $collectionId = $reservation->collection_id;

            $wasInQueue = in_array($oldStatus, ['active', 'notified'], true);
            $willBeInQueue = in_array($status, ['active', 'notified'], true);

            $payload = [
                'status' => $status,
            ];

            if ($status === 'notified') {
                $payload['notified_at'] = $reservation->notified_at ?? now();
                $payload['expires_at'] = $reservation->expires_at ?? now()->addDays($this->reservationPickupDays());
            }

            if ($status === 'fulfilled') {
                $payload['fulfilled_at'] = now();
            }

            if ($status === 'active' && ! $wasInQueue) {
                $payload['queue_position'] = $this->nextQueuePosition($collectionId);
                $payload['notified_at'] = null;
                $payload['expires_at'] = null;
                $payload['fulfilled_at'] = null;
            }

            $copy = null;

            if (in_array($status, ['cancelled', 'expired'], true) && $reservation->library_copy_id) {
                $copy = LibraryCopy::whereKey($reservation->library_copy_id)
                    ->lockForUpdate()
                    ->first();

                if ($copy && $copy->status === 'reserved') {
                    $copy->update([
                        'status' => 'available',
                    ]);
                }
            }

            $reservation->update($payload);

            if ($wasInQueue && ! $willBeInQueue) {
                $this->reorderQueueAfterLeaving($collectionId, $oldQueuePosition);
            }

            $fresh = $reservation->fresh(['collection.primaryAsset', 'libraryCopy']);

            $this->auditLogService->record(
                module: 'circulation',
                action: 'reservation_status_change',
                event: 'reservation.status_changed',
                actor: $actor,
                auditable: $fresh,
                oldValues: [
                    'status' => $oldValues['status'],
                    'queue_position' => $oldValues['queue_position'],
                    'library_copy_id' => $oldValues['library_copy_id'],
                    'notified_at' => $oldValues['notified_at'],
                    'expires_at' => $oldValues['expires_at'],
                    'fulfilled_at' => $oldValues['fulfilled_at'],
                ],
                newValues: [
                    'status' => $fresh->status,
                    'queue_position' => $fresh->queue_position,
                    'library_copy_id' => $fresh->library_copy_id,
                    'notified_at' => $fresh->notified_at,
                    'expires_at' => $fresh->expires_at,
                    'fulfilled_at' => $fresh->fulfilled_at,
                    'copy_status' => $copy?->fresh()?->status,
                ],
                metadata: [
                    'reason' => $reason,
                    'reservation_code' => $fresh->code,
                ]
            );

            return $fresh;
        });
    }

    private function nextQueuePosition(int $collectionId): int
    {
        $max = Reservation::where('collection_id', $collectionId)
            ->whereIn('status', ['active', 'notified'])
            ->lockForUpdate()
            ->max('queue_position');

        return ((int) $max) + 1;
    }

    private function reorderQueueAfterLeaving(int $collectionId, int $oldQueuePosition): void
    {
        Reservation::where('collection_id', $collectionId)
            ->whereIn('status', ['active', 'notified'])
            ->where('queue_position', '>', $oldQueuePosition)
            ->orderBy('queue_position')
            ->get()
            ->each(function (Reservation $reservation) {
                $reservation->update([
                    'queue_position' => max(1, $reservation->queue_position - 1),
                ]);
            });
    }

    private function reservationPickupDays(): int
    {
        $setting = SystemSetting::where('key', 'circulation.reservation_pickup_days')->first();

        $days = (int) data_get($setting?->value_json, 'days', 2);

        return $days > 0 ? $days : 2;
    }

    private function generateReservationCode(): string
    {
        do {
            $code = 'RSV-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
        } while (Reservation::where('code', $code)->exists());

        return $code;
    }
}