<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Borrowing;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function activeBorrowings(Request $request): JsonResponse
    {
        $member = $this->member($request);

        $borrowings = Borrowing::query()
            ->with([
                'libraryCopy.collection.primaryAsset',
                'libraryCopy.location',
            ])
            ->where('member_user_id', $member->id)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->orderBy('due_date')
            ->get();

        return response()->json([
            'data' => $borrowings->map(fn (Borrowing $borrowing) => $this->formatBorrowing($borrowing))->values(),
        ]);
    }

    public function borrowingHistory(Request $request): JsonResponse
    {
        $member = $this->member($request);

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $borrowings = Borrowing::query()
            ->with([
                'libraryCopy.collection.primaryAsset',
                'libraryCopy.location',
                'history',
                'finePayments',
            ])
            ->where('member_user_id', $member->id)
            ->orderByDesc('borrowed_at')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $borrowings->getCollection()
                ->map(fn (Borrowing $borrowing) => $this->formatBorrowing($borrowing, includeHistory: true))
                ->values(),
            'meta' => [
                'current_page' => $borrowings->currentPage(),
                'from' => $borrowings->firstItem(),
                'last_page' => $borrowings->lastPage(),
                'per_page' => $borrowings->perPage(),
                'to' => $borrowings->lastItem(),
                'total' => $borrowings->total(),
            ],
            'links' => [
                'first' => $borrowings->url(1),
                'last' => $borrowings->url($borrowings->lastPage()),
                'prev' => $borrowings->previousPageUrl(),
                'next' => $borrowings->nextPageUrl(),
            ],
        ]);
    }

    public function reservations(Request $request): JsonResponse
    {
        $member = $this->member($request);

        $perPage = (int) $request->query('per_page', 10);
        $perPage = max(1, min($perPage, 50));

        $reservations = Reservation::query()
            ->with([
                'collection.primaryAsset',
                'collection.category',
                'libraryCopy',
            ])
            ->where('member_user_id', $member->id)
            ->orderByDesc('reserved_at')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $reservations->getCollection()
                ->map(fn (Reservation $reservation) => $this->formatReservation($reservation))
                ->values(),
            'meta' => [
                'current_page' => $reservations->currentPage(),
                'from' => $reservations->firstItem(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'to' => $reservations->lastItem(),
                'total' => $reservations->total(),
            ],
            'links' => [
                'first' => $reservations->url(1),
                'last' => $reservations->url($reservations->lastPage()),
                'prev' => $reservations->previousPageUrl(),
                'next' => $reservations->nextPageUrl(),
            ],
        ]);
    }

    public function bookmarks(Request $request): JsonResponse
    {
        $member = $this->member($request);

        $bookmarks = $member->bookmarks()
            ->with([
                'category',
                'primaryAsset',
                'creators',
                'subjects',
                'libraryItem',
                'museumItem',
            ])
            ->orderByDesc('user_bookmarks.created_at')
            ->get();

        return response()->json([
            'data' => $bookmarks->map(fn ($collection) => [
                'id' => $collection->id,
                'ulid' => $collection->ulid,
                'record_code' => $collection->record_code,
                'unit_type' => $collection->unit_type,
                'collection_type' => $collection->collection_type,
                'title' => $collection->title,
                'subtitle' => $collection->subtitle,
                'display_title' => $collection->display_title,
                'description' => $collection->description,
                'date_display' => $collection->date_display,
                'visibility' => $collection->visibility,
                'category' => $collection->category ? [
                    'id' => $collection->category->id,
                    'name' => $collection->category->name,
                    'slug' => $collection->category->slug,
                ] : null,
                'primary_asset' => $this->formatAsset($collection->primaryAsset),
                'bookmark' => [
                    'folder_name' => $collection->pivot->folder_name,
                    'notes' => $collection->pivot->notes,
                    'created_at' => $collection->pivot->created_at,
                ],
            ])->values(),
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

    private function formatBorrowing(Borrowing $borrowing, bool $includeHistory = false): array
    {
        $copy = $borrowing->libraryCopy;
        $collection = $copy?->collection;

        $data = [
            'id' => $borrowing->id,
            'ulid' => $borrowing->ulid,
            'transaction_code' => $borrowing->transaction_code,
            'borrowed_at' => $borrowing->borrowed_at,
            'due_date' => $borrowing->due_date,
            'returned_at' => $borrowing->returned_at,
            'status' => $borrowing->status,
            'renewal_count' => $borrowing->renewal_count,
            'fine_amount' => $borrowing->fine_amount,
            'fine_paid_amount' => $borrowing->fine_paid_amount,
            'fine_paid_at' => $borrowing->fine_paid_at,
            'has_unpaid_fine' => $borrowing->hasUnpaidFine(),
            'copy' => $copy ? [
                'id' => $copy->id,
                'copy_number' => $copy->copy_number,
                'barcode' => $copy->barcode,
                'call_number' => $copy->call_number,
                'condition_grade' => $copy->condition_grade,
                'status' => $copy->status,
                'location' => $copy->location ? [
                    'id' => $copy->location->id,
                    'code' => $copy->location->code,
                    'name' => $copy->location->name,
                ] : null,
            ] : null,
            'collection' => $collection ? [
                'id' => $collection->id,
                'ulid' => $collection->ulid,
                'record_code' => $collection->record_code,
                'title' => $collection->title,
                'display_title' => $collection->display_title,
                'collection_type' => $collection->collection_type,
                'primary_asset' => $this->formatAsset($collection->primaryAsset),
            ] : null,
        ];

        if ($includeHistory) {
            $data['history'] = $borrowing->history
                ->sortBy('event_at')
                ->map(fn ($history) => [
                    'id' => $history->id,
                    'event_type' => $history->event_type,
                    'event_at' => $history->event_at,
                    'old_status' => $history->old_status,
                    'new_status' => $history->new_status,
                    'old_due_date' => $history->old_due_date,
                    'new_due_date' => $history->new_due_date,
                    'amount' => $history->amount,
                    'notes' => $history->notes,
                ])
                ->values();

            $data['fine_payments'] = $borrowing->finePayments
                ->map(fn ($fine) => [
                    'id' => $fine->id,
                    'fine_code' => $fine->fine_code,
                    'paid_amount' => $fine->paid_amount,
                    'waived_amount' => $fine->waived_amount,
                    'status' => $fine->status,
                    'paid_at' => $fine->paid_at,
                    'notes' => $fine->notes,
                ])
                ->values();
        }

        return $data;
    }

    private function formatReservation(Reservation $reservation): array
    {
        $collection = $reservation->collection;

        return [
            'id' => $reservation->id,
            'ulid' => $reservation->ulid,
            'code' => $reservation->code,
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
                'primary_asset' => $this->formatAsset($collection->primaryAsset),
            ] : null,
            'allocated_copy' => $reservation->libraryCopy ? [
                'id' => $reservation->libraryCopy->id,
                'copy_number' => $reservation->libraryCopy->copy_number,
                'barcode' => $reservation->libraryCopy->barcode,
                'status' => $reservation->libraryCopy->status,
            ] : null,
        ];
    }

    private function formatAsset($asset): ?array
    {
        if (! $asset) {
            return null;
        }

        return [
            'id' => $asset->id,
            'ulid' => $asset->ulid,
            'asset_type' => $asset->asset_type,
            'file_role' => $asset->file_role,
            'path' => $asset->path,
            'thumbnail_path' => $asset->thumbnail_path,
            'watermarked_path' => $asset->watermarked_path,
            'mime_type' => $asset->mime_type,
            'caption' => $asset->caption,
            'access_level' => $asset->access_level,
        ];
    }
}