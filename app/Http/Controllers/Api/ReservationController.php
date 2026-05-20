<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection as CollectionModel;
use App\Models\LibraryCopy;
use App\Models\Reservation;
use App\Models\User;
use App\Services\ReservationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    public function store(Request $request, ReservationService $reservationService): JsonResponse
    {
        $member = $this->member($request);

        $validated = $request->validate([
            'collection_id' => ['sometimes', 'nullable', 'integer', 'exists:collections,id'],
            'record_code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (empty($validated['collection_id']) && empty($validated['record_code'])) {
            return response()->json([
                'message' => 'collection_id atau record_code wajib diisi.',
            ], 422);
        }

        $collection = $this->resolveCollection($validated);

        $reservation = $reservationService->create(
            member: $member,
            collection: $collection,
            reservedAt: now(),
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Reservasi berhasil dibuat.',
            'data' => $this->formatReservation($reservation),
        ], 201);
    }

    public function cancel(Request $request, Reservation $reservation, ReservationService $reservationService): JsonResponse
    {
        $actor = $request->user();

        abort_if(! $actor, 401, 'Unauthenticated.');

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $cancelled = $reservationService->cancel(
            reservation: $reservation,
            actor: $actor,
            reason: $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Reservasi berhasil dibatalkan.',
            'data' => $this->formatReservation($cancelled),
        ]);
    }

    public function allocateCopy(Request $request, Reservation $reservation, ReservationService $reservationService): JsonResponse
    {
        $actor = $this->staff($request);

        $validated = $request->validate([
            'library_copy_id' => ['sometimes', 'nullable', 'integer', 'exists:library_copies,id'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:120'],
            'expires_at' => ['sometimes', 'nullable', 'date'],
        ]);

        if (empty($validated['library_copy_id']) && empty($validated['barcode'])) {
            return response()->json([
                'message' => 'library_copy_id atau barcode wajib diisi.',
            ], 422);
        }

        $copy = $this->resolveCopy($validated);

        $updated = $reservationService->allocateCopy(
            reservation: $reservation,
            copy: $copy,
            actor: $actor,
            expiresAt: isset($validated['expires_at'])
                ? \Carbon\Carbon::parse($validated['expires_at'])
                : null
        );

        return response()->json([
            'message' => 'Copy berhasil dialokasikan ke reservasi.',
            'data' => $this->formatReservation($updated),
        ]);
    }

    public function updateStatus(Request $request, Reservation $reservation, ReservationService $reservationService): JsonResponse
    {
        $actor = $this->staff($request);

        $validated = $request->validate([
            'status' => ['required', 'in:active,notified,fulfilled,cancelled,expired'],
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $updated = $reservationService->changeStatus(
            reservation: $reservation,
            status: $validated['status'],
            actor: $actor,
            reason: $validated['reason'] ?? null
        );

        return response()->json([
            'message' => 'Status reservasi berhasil diperbarui.',
            'data' => $this->formatReservation($updated),
        ]);
    }

    private function member(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');
        abort_if(! $user->hasRole('member'), 403, 'Endpoint ini hanya untuk member.');

        return $user;
    }

    private function staff(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');
        abort_if(! $user->hasAnyRole(['admin', 'pustakawan']), 403, 'Endpoint ini hanya untuk admin atau pustakawan.');

        return $user;
    }

    private function resolveCollection(array $validated): CollectionModel
    {
        if (! empty($validated['collection_id'])) {
            return CollectionModel::findOrFail($validated['collection_id']);
        }

        return CollectionModel::where('record_code', $validated['record_code'])
            ->orWhere('ulid', $validated['record_code'])
            ->firstOrFail();
    }

    private function resolveCopy(array $validated): LibraryCopy
    {
        if (! empty($validated['library_copy_id'])) {
            return LibraryCopy::findOrFail($validated['library_copy_id']);
        }

        return LibraryCopy::where('barcode', $validated['barcode'])->firstOrFail();
    }

    private function formatReservation(Reservation $reservation): array
    {
        $reservation->loadMissing([
            'collection.primaryAsset',
            'collection.category',
            'libraryCopy.location',
        ]);

        $collection = $reservation->collection;
        $copy = $reservation->libraryCopy;

        return [
            'id' => $reservation->id,
            'ulid' => $reservation->ulid,
            'code' => $reservation->code,
            'member_user_id' => $reservation->member_user_id,
            'collection_id' => $reservation->collection_id,
            'library_copy_id' => $reservation->library_copy_id,
            'queue_position' => $reservation->queue_position,
            'status' => $reservation->status,
            'reserved_at' => $reservation->reserved_at,
            'expires_at' => $reservation->expires_at,
            'notified_at' => $reservation->notified_at,
            'fulfilled_at' => $reservation->fulfilled_at,
            'collection' => $collection ? [
                'id' => $collection->id,
                'ulid' => $collection->ulid,
                'record_code' => $collection->record_code,
                'title' => $collection->title,
                'display_title' => $collection->display_title,
                'unit_type' => $collection->unit_type,
                'collection_type' => $collection->collection_type,
                'visibility' => $collection->visibility,
                'category' => $collection->category ? [
                    'id' => $collection->category->id,
                    'name' => $collection->category->name,
                    'slug' => $collection->category->slug,
                ] : null,
                'primary_asset' => $collection->primaryAsset ? [
                    'id' => $collection->primaryAsset->id,
                    'asset_type' => $collection->primaryAsset->asset_type,
                    'path' => $collection->primaryAsset->path,
                    'thumbnail_path' => $collection->primaryAsset->thumbnail_path,
                ] : null,
            ] : null,
            'allocated_copy' => $copy ? [
                'id' => $copy->id,
                'copy_number' => $copy->copy_number,
                'barcode' => $copy->barcode,
                'call_number' => $copy->call_number,
                'status' => $copy->status,
                'location' => $copy->location ? [
                    'id' => $copy->location->id,
                    'code' => $copy->location->code,
                    'name' => $copy->location->name,
                ] : null,
            ] : null,
        ];
    }
}