<?php

namespace App\Services;

use App\Models\BorrowHistory;
use App\Models\Borrowing;
use App\Models\BorrowRenewal;
use App\Models\Fine;
use App\Models\LibraryCopy;
use App\Models\SystemSetting;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CirculationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function borrow(
        User $member,
        LibraryCopy $copy,
        ?User $processedBy = null,
        ?CarbonInterface $borrowedAt = null,
        ?int $durationDays = null,
        ?string $notes = null
    ): Borrowing {
        return DB::transaction(function () use ($member, $copy, $processedBy, $borrowedAt, $durationDays, $notes) {
            $member = User::whereKey($member->id)
                ->lockForUpdate()
                ->firstOrFail();

            $copy = LibraryCopy::with('collection')
                ->whereKey($copy->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $copy->collection || $copy->collection->unit_type !== 'library') {
                throw new RuntimeException('Only library collection copies can be borrowed.');
            }

            if (! $member->canBorrow()) {
                throw new RuntimeException('Member is not eligible to borrow.');
            }

            if ($copy->status !== 'available') {
                throw new RuntimeException('Library copy is not available.');
            }

            $hasActiveBorrowingForCopy = Borrowing::where('library_copy_id', $copy->id)
                ->whereIn('status', ['borrowed', 'overdue'])
                ->lockForUpdate()
                ->exists();

            if ($hasActiveBorrowingForCopy) {
                throw new RuntimeException('Library copy already has active borrowing.');
            }

            $durationDays = $durationDays ?? $member->defaultBorrowDurationDays();

            if ($durationDays <= 0) {
                throw new RuntimeException('Borrow duration is not configured for this member role.');
            }

            $borrowedAt = $this->asCarbon($borrowedAt);
            $dueDate = $borrowedAt->copy()->addDays($durationDays)->toDateString();

            $borrowing = Borrowing::create([
                'ulid' => (string) Str::ulid(),
                'transaction_code' => $this->generateBorrowingCode(),
                'member_user_id' => $member->id,
                'library_copy_id' => $copy->id,
                'borrowed_at' => $borrowedAt,
                'due_date' => $dueDate,
                'returned_at' => null,
                'status' => 'borrowed',
                'renewal_count' => 0,
                'fine_amount' => 0,
                'fine_paid_amount' => 0,
                'fine_paid_at' => null,
                'processed_by' => $processedBy?->id,
                'returned_by' => null,
                'notes' => $notes,
            ]);

            $copy->update([
                'status' => 'borrowed',
            ]);

            $this->recordHistory(
                borrowing: $borrowing,
                eventType: 'borrowed',
                actor: $processedBy,
                eventAt: $borrowedAt,
                oldStatus: null,
                newStatus: 'borrowed',
                oldDueDate: null,
                newDueDate: $dueDate,
                amount: null,
                notes: $notes
            );

            $this->auditLogService->record(
                module: 'circulation',
                action: 'borrow',
                event: 'circulation.borrowed',
                actor: $processedBy,
                auditable: $borrowing,
                oldValues: null,
                newValues: [
                    'transaction_code' => $borrowing->transaction_code,
                    'member_user_id' => $member->id,
                    'library_copy_id' => $copy->id,
                    'borrowed_at' => $borrowedAt->toDateTimeString(),
                    'due_date' => $dueDate,
                    'status' => 'borrowed',
                ],
                metadata: [
                    'record_code' => $copy->collection->record_code,
                    'barcode' => $copy->barcode,
                ]
            );

            return $borrowing->fresh(['member', 'libraryCopy']);
        });
    }

    public function renew(
        Borrowing $borrowing,
        User $renewedBy,
        ?int $extendDays = null,
        string $renewalMethod = 'counter',
        ?string $notes = null
    ): Borrowing {
        return DB::transaction(function () use ($borrowing, $renewedBy, $extendDays, $renewalMethod, $notes) {
            if (! in_array($renewalMethod, ['online', 'counter'], true)) {
                throw new RuntimeException('Invalid renewal method.');
            }

            $borrowing = Borrowing::with(['member', 'libraryCopy'])
                ->whereKey($borrowing->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($borrowing->status !== 'borrowed') {
                throw new RuntimeException('Only active borrowed items can be renewed.');
            }

            $today = now()->startOfDay();
            $oldDueDate = Carbon::parse($borrowing->due_date)->startOfDay();

            if ($oldDueDate->lt($today)) {
                throw new RuntimeException('Overdue borrowing cannot be renewed.');
            }

            $maxRenewalCount = $this->maxRenewalCount();

            if ($borrowing->renewal_count >= $maxRenewalCount) {
                throw new RuntimeException('Maximum renewal count has been reached.');
            }

            $member = User::whereKey($borrowing->member_user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $extendDays = $extendDays ?? $member->defaultBorrowDurationDays();

            if ($extendDays <= 0) {
                throw new RuntimeException('Renewal duration is not configured for this member role.');
            }

            $newDueDate = $oldDueDate->copy()->addDays($extendDays)->toDateString();

            BorrowRenewal::create([
                'borrowing_id' => $borrowing->id,
                'renewed_by' => $renewedBy->id,
                'old_due_date' => $oldDueDate->toDateString(),
                'new_due_date' => $newDueDate,
                'renewal_method' => $renewalMethod,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            $borrowing->update([
                'due_date' => $newDueDate,
                'renewal_count' => $borrowing->renewal_count + 1,
            ]);

            $fresh = $borrowing->fresh(['member', 'libraryCopy']);

            $this->recordHistory(
                borrowing: $fresh,
                eventType: 'renewed',
                actor: $renewedBy,
                eventAt: now(),
                oldStatus: 'borrowed',
                newStatus: 'borrowed',
                oldDueDate: $oldDueDate->toDateString(),
                newDueDate: $newDueDate,
                amount: null,
                notes: $notes
            );

            $this->auditLogService->record(
                module: 'circulation',
                action: 'renew',
                event: 'circulation.renewed',
                actor: $renewedBy,
                auditable: $fresh,
                oldValues: [
                    'due_date' => $oldDueDate->toDateString(),
                    'renewal_count' => $borrowing->renewal_count,
                ],
                newValues: [
                    'due_date' => $newDueDate,
                    'renewal_count' => $fresh->renewal_count,
                ],
                metadata: [
                    'transaction_code' => $fresh->transaction_code,
                    'renewal_method' => $renewalMethod,
                ]
            );

            return $fresh;
        });
    }

    public function returnItem(
        Borrowing $borrowing,
        ?User $returnedBy = null,
        ?CarbonInterface $returnedAt = null,
        ?string $notes = null
    ): Borrowing {
        return DB::transaction(function () use ($borrowing, $returnedBy, $returnedAt, $notes) {
            $borrowing = Borrowing::whereKey($borrowing->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($borrowing->status, ['borrowed', 'overdue'], true)) {
                throw new RuntimeException('Only borrowed or overdue items can be returned.');
            }

            $copy = LibraryCopy::whereKey($borrowing->library_copy_id)
                ->lockForUpdate()
                ->firstOrFail();

            $returnedAt = $this->asCarbon($returnedAt);
            $oldStatus = $borrowing->status;
            $fineAmount = $this->calculateFine($borrowing, $returnedAt);

            $borrowing->update([
                'returned_at' => $returnedAt,
                'status' => 'returned',
                'fine_amount' => $fineAmount,
                'returned_by' => $returnedBy?->id,
                'notes' => $notes ?? $borrowing->notes,
            ]);

            $copy->update([
                'status' => 'available',
            ]);

            $fresh = $borrowing->fresh(['member', 'libraryCopy']);

            $this->recordHistory(
                borrowing: $fresh,
                eventType: 'returned',
                actor: $returnedBy,
                eventAt: $returnedAt,
                oldStatus: $oldStatus,
                newStatus: 'returned',
                oldDueDate: $borrowing->due_date?->toDateString(),
                newDueDate: $borrowing->due_date?->toDateString(),
                amount: $fineAmount,
                notes: $notes
            );

            if ($fineAmount > 0) {
                $this->recordHistory(
                    borrowing: $fresh,
                    eventType: 'fine_calculated',
                    actor: $returnedBy,
                    eventAt: $returnedAt,
                    oldStatus: $oldStatus,
                    newStatus: 'returned',
                    oldDueDate: $borrowing->due_date?->toDateString(),
                    newDueDate: $borrowing->due_date?->toDateString(),
                    amount: $fineAmount,
                    notes: 'Fine calculated on return.'
                );
            }

            $this->auditLogService->record(
                module: 'circulation',
                action: 'return',
                event: 'circulation.returned',
                actor: $returnedBy,
                auditable: $fresh,
                oldValues: [
                    'status' => $oldStatus,
                    'returned_at' => null,
                    'fine_amount' => (float) $borrowing->fine_amount,
                    'copy_status' => 'borrowed',
                ],
                newValues: [
                    'status' => 'returned',
                    'returned_at' => $returnedAt->toDateTimeString(),
                    'fine_amount' => $fineAmount,
                    'copy_status' => 'available',
                ],
                metadata: [
                    'transaction_code' => $fresh->transaction_code,
                    'library_copy_id' => $copy->id,
                    'barcode' => $copy->barcode,
                ]
            );

            return $fresh;
        });
    }

    public function payFine(
        Borrowing $borrowing,
        float $paidAmount,
        float $waivedAmount = 0.0,
        ?User $processedBy = null,
        ?string $notes = null
    ): Fine {
        return DB::transaction(function () use ($borrowing, $paidAmount, $waivedAmount, $processedBy, $notes) {
            if ($paidAmount < 0 || $waivedAmount < 0) {
                throw new RuntimeException('Paid amount and waived amount cannot be negative.');
            }

            $settlementAmount = round($paidAmount + $waivedAmount, 2);

            if ($settlementAmount <= 0) {
                throw new RuntimeException('Fine settlement amount must be greater than zero.');
            }

            $borrowing = Borrowing::whereKey($borrowing->id)
                ->lockForUpdate()
                ->firstOrFail();

            $fineAmount = (float) $borrowing->fine_amount;
            $finePaidAmount = (float) $borrowing->fine_paid_amount;
            $remaining = round($fineAmount - $finePaidAmount, 2);

            if ($fineAmount <= 0) {
                throw new RuntimeException('Borrowing has no fine.');
            }

            if ($remaining <= 0) {
                throw new RuntimeException('Fine has already been settled.');
            }

            if ($settlementAmount > $remaining) {
                throw new RuntimeException('Settlement amount cannot exceed remaining fine.');
            }

            $willBeSettled = $settlementAmount >= $remaining;

            $status = match (true) {
                $willBeSettled && $paidAmount > 0 => 'paid',
                $willBeSettled && $paidAmount <= 0 && $waivedAmount > 0 => 'waived',
                default => 'partially_paid',
            };

            $fine = Fine::create([
                'fine_code' => $this->generateFineCode(),
                'borrowing_id' => $borrowing->id,
                'member_user_id' => $borrowing->member_user_id,
                'paid_amount' => $paidAmount,
                'waived_amount' => $waivedAmount,
                'status' => $status,
                'paid_at' => now(),
                'processed_by' => $processedBy?->id,
                'notes' => $notes,
            ]);

            $borrowing->syncFinePaymentStatus();

            $freshBorrowing = $borrowing->fresh();

            $this->recordHistory(
                borrowing: $freshBorrowing,
                eventType: 'fine_paid',
                actor: $processedBy,
                eventAt: now(),
                oldStatus: $freshBorrowing->status,
                newStatus: $freshBorrowing->status,
                oldDueDate: $freshBorrowing->due_date?->toDateString(),
                newDueDate: $freshBorrowing->due_date?->toDateString(),
                amount: $settlementAmount,
                notes: $notes
            );

            $this->auditLogService->record(
                module: 'circulation',
                action: 'fine_payment',
                event: 'circulation.fine_paid',
                actor: $processedBy,
                auditable: $freshBorrowing,
                oldValues: [
                    'fine_paid_amount' => $finePaidAmount,
                    'remaining_fine' => $remaining,
                ],
                newValues: [
                    'fine_paid_amount' => (float) $freshBorrowing->fine_paid_amount,
                    'fine_paid_at' => $freshBorrowing->fine_paid_at?->toDateTimeString(),
                    'paid_amount' => $paidAmount,
                    'waived_amount' => $waivedAmount,
                    'status' => $status,
                ],
                metadata: [
                    'fine_code' => $fine->fine_code,
                    'transaction_code' => $freshBorrowing->transaction_code,
                ]
            );

            return $fine->fresh();
        });
    }

    public function calculateFine(Borrowing $borrowing, ?CarbonInterface $returnDate = null): float
    {
        $dueDate = Carbon::parse($borrowing->due_date)->startOfDay();
        $returnDate = $this->asCarbon($returnDate)->startOfDay();

        if ($returnDate->lte($dueDate)) {
            return 0.00;
        }

        $daysLate = (int) $dueDate->diffInDays($returnDate);
        $finePerDay = $this->finePerDay();

        return round($daysLate * $finePerDay, 2);
    }

    private function recordHistory(
        Borrowing $borrowing,
        string $eventType,
        ?User $actor,
        CarbonInterface $eventAt,
        ?string $oldStatus,
        ?string $newStatus,
        ?string $oldDueDate,
        ?string $newDueDate,
        ?float $amount,
        ?string $notes
    ): BorrowHistory {
        return BorrowHistory::create([
            'borrowing_id' => $borrowing->id,
            'library_copy_id' => $borrowing->library_copy_id,
            'member_user_id' => $borrowing->member_user_id,
            'event_type' => $eventType,
            'event_at' => $eventAt,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'old_due_date' => $oldDueDate,
            'new_due_date' => $newDueDate,
            'amount' => $amount,
            'actor_id' => $actor?->id,
            'notes' => $notes,
            'created_at' => now(),
        ]);
    }

    private function finePerDay(): float
    {
        $setting = SystemSetting::where('key', 'circulation.default_fine_per_day')->first();

        return (float) data_get($setting?->value_json, 'amount', 0);
    }

    private function maxRenewalCount(): int
    {
        $setting = SystemSetting::where('key', 'circulation.max_renewal_count')->first();

        return (int) data_get($setting?->value_json, 'count', 1);
    }

    private function generateBorrowingCode(): string
    {
        do {
            $code = 'BRW-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
        } while (Borrowing::where('transaction_code', $code)->exists());

        return $code;
    }

    private function generateFineCode(): string
    {
        do {
            $code = 'FINE-' . now()->format('Ymd-His') . '-' . random_int(100, 999);
        } while (Fine::where('fine_code', $code)->exists());

        return $code;
    }

    private function asCarbon(?CarbonInterface $dateTime): Carbon
    {
        if ($dateTime === null) {
            return now();
        }

        return Carbon::parse($dateTime->format('Y-m-d H:i:s'));
    }
}