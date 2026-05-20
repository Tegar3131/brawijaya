<?php

namespace App\Services;

use App\Models\Borrowing;
use App\Models\Collection as CollectionModel;
use App\Models\ConditionReport;
use App\Models\DigitalAsset;
use App\Models\Fine;
use App\Models\LibraryCopy;
use App\Models\MuseumItem;
use App\Models\Reservation;
use App\Models\SearchLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function buildFor(User $user): array
    {
        if ($user->hasRole('admin')) {
            return $this->adminDashboard($user);
        }

        if ($user->hasRole('pustakawan')) {
            return $this->libraryDashboard($user);
        }

        if ($user->hasRole('kurator')) {
            return $this->museumDashboard($user);
        }

        if ($user->hasRole('member')) {
            return $this->memberDashboard($user);
        }

        return $this->guestLikeDashboard($user);
    }

    private function adminDashboard(User $user): array
    {
        return [
            'role' => 'admin',
            'generated_at' => now(),
            'user' => $this->formatUser($user),
            'collections' => [
                'total_active' => CollectionModel::count(),
                'total_with_archived' => CollectionModel::withTrashed()->count(),
                'library' => CollectionModel::where('unit_type', 'library')->count(),
                'museum' => CollectionModel::where('unit_type', 'museum')->count(),
                'published' => CollectionModel::where('publication_status', 'published')->count(),
                'draft' => CollectionModel::where('publication_status', 'draft')->count(),
                'restricted' => CollectionModel::where('publication_status', 'restricted')->count(),
                'archived' => CollectionModel::onlyTrashed()->count(),
            ],
            'digital_assets' => [
                'total' => DigitalAsset::count(),
                'public' => DigitalAsset::where('access_level', 'public')->count(),
                'member' => DigitalAsset::where('access_level', 'member')->count(),
                'internal' => DigitalAsset::where('access_level', 'internal')->count(),
                'restricted' => DigitalAsset::where('access_level', 'restricted')->count(),
            ],
            'users' => [
                'total' => User::count(),
                'members' => User::role('member')->count(),
                'admin' => User::role('admin')->count(),
                'pustakawan' => User::role('pustakawan')->count(),
                'kurator' => User::role('kurator')->count(),
                'active_members' => User::role('member')
                    ->where('membership_status', 'active')
                    ->count(),
            ],
            'circulation' => [
                'active_borrowings' => Borrowing::whereIn('status', ['borrowed', 'overdue'])->count(),
                'overdue_borrowings' => Borrowing::whereIn('status', ['borrowed', 'overdue'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
                'returned_borrowings' => Borrowing::where('status', 'returned')->count(),
                'unpaid_fine_borrowings' => Borrowing::whereColumn('fine_amount', '>', 'fine_paid_amount')->count(),
                'fine_payments' => Fine::count(),
                'active_reservations' => Reservation::whereIn('status', ['active', 'notified'])->count(),
            ],
            'museum' => [
                'museum_items' => MuseumItem::count(),
                'condition_reports' => ConditionReport::count(),
                'condition_review_due' => ConditionReport::whereNotNull('next_review_at')
                    ->whereDate('next_review_at', '<=', now()->toDateString())
                    ->count(),
            ],
            'activity' => [
                'search_logs_today' => SearchLog::whereDate('created_at', now()->toDateString())->count(),
                'latest_searches' => SearchLog::latest('created_at')
                    ->limit(5)
                    ->get(['id', 'query', 'results_count', 'collection_type', 'created_at'])
                    ->toArray(),
            ],
        ];
    }

    private function libraryDashboard(User $user): array
    {
        $libraryCollectionIds = CollectionModel::where('unit_type', 'library')->select('id');

        return [
            'role' => 'pustakawan',
            'generated_at' => now(),
            'user' => $this->formatUser($user),
            'collections' => [
                'library_total' => CollectionModel::where('unit_type', 'library')->count(),
                'published' => CollectionModel::where('unit_type', 'library')
                    ->where('publication_status', 'published')
                    ->count(),
                'draft' => CollectionModel::where('unit_type', 'library')
                    ->where('publication_status', 'draft')
                    ->count(),
                'archived' => CollectionModel::onlyTrashed()
                    ->where('unit_type', 'library')
                    ->count(),
            ],
            'copies' => [
                'total' => LibraryCopy::count(),
                'by_status' => $this->countByStatus(LibraryCopy::query(), 'status'),
                'available' => LibraryCopy::where('status', 'available')->count(),
                'borrowed' => LibraryCopy::where('status', 'borrowed')->count(),
                'reserved' => LibraryCopy::where('status', 'reserved')->count(),
            ],
            'circulation' => [
                'active_borrowings' => Borrowing::whereIn('status', ['borrowed', 'overdue'])->count(),
                'overdue_borrowings' => Borrowing::whereIn('status', ['borrowed', 'overdue'])
                    ->whereDate('due_date', '<', now()->toDateString())
                    ->count(),
                'returned_this_month' => Borrowing::where('status', 'returned')
                    ->whereMonth('returned_at', now()->month)
                    ->whereYear('returned_at', now()->year)
                    ->count(),
                'unpaid_fine_borrowings' => Borrowing::whereColumn('fine_amount', '>', 'fine_paid_amount')->count(),
            ],
            'reservations' => [
                'active' => Reservation::where('status', 'active')->count(),
                'notified' => Reservation::where('status', 'notified')->count(),
                'expired' => Reservation::where('status', 'expired')->count(),
                'cancelled' => Reservation::where('status', 'cancelled')->count(),
            ],
            'digital_assets' => [
                'total_library_assets' => DigitalAsset::whereIn('collection_id', $libraryCollectionIds)->count(),
            ],
            'latest' => [
                'collections' => CollectionModel::where('unit_type', 'library')
                    ->latest('created_at')
                    ->limit(5)
                    ->get(['id', 'record_code', 'title', 'collection_type', 'publication_status', 'visibility', 'created_at'])
                    ->toArray(),
                'borrowings' => Borrowing::with('libraryCopy.collection')
                    ->latest('borrowed_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Borrowing $borrowing) => [
                        'id' => $borrowing->id,
                        'transaction_code' => $borrowing->transaction_code,
                        'status' => $borrowing->status,
                        'borrowed_at' => $borrowing->borrowed_at,
                        'due_date' => $borrowing->due_date,
                        'collection_title' => $borrowing->libraryCopy?->collection?->title,
                        'barcode' => $borrowing->libraryCopy?->barcode,
                    ])
                    ->values(),
            ],
        ];
    }

    private function museumDashboard(User $user): array
    {
        $museumCollectionIds = CollectionModel::where('unit_type', 'museum')->select('id');

        return [
            'role' => 'kurator',
            'generated_at' => now(),
            'user' => $this->formatUser($user),
            'collections' => [
                'museum_total' => CollectionModel::where('unit_type', 'museum')->count(),
                'published' => CollectionModel::where('unit_type', 'museum')
                    ->where('publication_status', 'published')
                    ->count(),
                'draft' => CollectionModel::where('unit_type', 'museum')
                    ->where('publication_status', 'draft')
                    ->count(),
                'archived' => CollectionModel::onlyTrashed()
                    ->where('unit_type', 'museum')
                    ->count(),
            ],
            'museum_items' => [
                'total' => MuseumItem::count(),
                'by_condition' => $this->countByStatus(MuseumItem::query(), 'condition_current'),
                'sensitive' => MuseumItem::where('is_sensitive', true)->count(),
            ],
            'condition_reports' => [
                'total' => ConditionReport::count(),
                'urgent' => ConditionReport::where('priority', 'urgent')->count(),
                'maintenance' => ConditionReport::where('priority', 'maintenance')->count(),
                'review_due' => ConditionReport::whereNotNull('next_review_at')
                    ->whereDate('next_review_at', '<=', now()->toDateString())
                    ->count(),
            ],
            'digital_assets' => [
                'total_museum_assets' => DigitalAsset::whereIn('collection_id', $museumCollectionIds)->count(),
                'photos' => DigitalAsset::whereIn('collection_id', $museumCollectionIds)
                    ->where('asset_type', 'photo')
                    ->count(),
                'documents' => DigitalAsset::whereIn('collection_id', $museumCollectionIds)
                    ->whereIn('asset_type', ['document', 'pdf'])
                    ->count(),
            ],
            'latest' => [
                'collections' => CollectionModel::where('unit_type', 'museum')
                    ->latest('created_at')
                    ->limit(5)
                    ->get(['id', 'record_code', 'title', 'collection_type', 'publication_status', 'visibility', 'created_at'])
                    ->toArray(),
                'condition_reports' => ConditionReport::with('museumItem.collection')
                    ->latest('inspected_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (ConditionReport $report) => [
                        'id' => $report->id,
                        'condition_grade' => $report->condition_grade,
                        'priority' => $report->priority,
                        'inspected_at' => $report->inspected_at,
                        'next_review_at' => $report->next_review_at,
                        'collection_title' => $report->museumItem?->collection?->title,
                        'inventory_number' => $report->museumItem?->inventory_number,
                    ])
                    ->values(),
            ],
        ];
    }

    private function memberDashboard(User $user): array
    {
        $activeBorrowings = Borrowing::with('libraryCopy.collection.primaryAsset')
            ->where('member_user_id', $user->id)
            ->whereIn('status', ['borrowed', 'overdue'])
            ->orderBy('due_date')
            ->get();

        $unpaidFineAmount = Borrowing::where('member_user_id', $user->id)
            ->selectRaw('COALESCE(SUM(fine_amount - fine_paid_amount), 0) AS unpaid_total')
            ->value('unpaid_total');

        return [
            'role' => 'member',
            'generated_at' => now(),
            'user' => $this->formatUser($user),
            'membership' => [
                'member_number' => $user->member_number,
                'membership_status' => $user->membership_status,
                'member_active_until' => $user->member_active_until,
                'has_active_membership' => $user->hasActiveMembership(),
                'can_borrow' => $user->canBorrow(),
                'max_borrow_items' => $user->roles()->first()?->max_borrow_items,
                'borrow_duration_days' => $user->defaultBorrowDurationDays(),
            ],
            'borrowings' => [
                'active_count' => $activeBorrowings->count(),
                'overdue_count' => $activeBorrowings
                    ->filter(fn (Borrowing $borrowing) => $borrowing->due_date && $borrowing->due_date->isPast())
                    ->count(),
                'history_count' => Borrowing::where('member_user_id', $user->id)->count(),
                'active_items' => $activeBorrowings->map(fn (Borrowing $borrowing) => [
                    'id' => $borrowing->id,
                    'transaction_code' => $borrowing->transaction_code,
                    'status' => $borrowing->status,
                    'borrowed_at' => $borrowing->borrowed_at,
                    'due_date' => $borrowing->due_date,
                    'renewal_count' => $borrowing->renewal_count,
                    'fine_amount' => $borrowing->fine_amount,
                    'fine_paid_amount' => $borrowing->fine_paid_amount,
                    'collection' => $borrowing->libraryCopy?->collection ? [
                        'id' => $borrowing->libraryCopy->collection->id,
                        'record_code' => $borrowing->libraryCopy->collection->record_code,
                        'title' => $borrowing->libraryCopy->collection->title,
                    ] : null,
                    'copy' => $borrowing->libraryCopy ? [
                        'id' => $borrowing->libraryCopy->id,
                        'barcode' => $borrowing->libraryCopy->barcode,
                        'call_number' => $borrowing->libraryCopy->call_number,
                    ] : null,
                ])->values(),
            ],
            'fines' => [
                'unpaid_borrowing_count' => Borrowing::where('member_user_id', $user->id)
                    ->whereColumn('fine_amount', '>', 'fine_paid_amount')
                    ->count(),
                'unpaid_total' => (float) $unpaidFineAmount,
            ],
            'reservations' => [
                'active' => Reservation::where('member_user_id', $user->id)
                    ->where('status', 'active')
                    ->count(),
                'notified' => Reservation::where('member_user_id', $user->id)
                    ->where('status', 'notified')
                    ->count(),
                'latest' => Reservation::with('collection')
                    ->where('member_user_id', $user->id)
                    ->latest('reserved_at')
                    ->limit(5)
                    ->get()
                    ->map(fn (Reservation $reservation) => [
                        'id' => $reservation->id,
                        'code' => $reservation->code,
                        'status' => $reservation->status,
                        'queue_position' => $reservation->queue_position,
                        'reserved_at' => $reservation->reserved_at,
                        'expires_at' => $reservation->expires_at,
                        'collection_title' => $reservation->collection?->title,
                    ])
                    ->values(),
            ],
            'bookmarks' => [
                'total' => $user->bookmarks()->count(),
                'latest' => $user->bookmarks()
                    ->latest('user_bookmarks.created_at')
                    ->limit(5)
                    ->get(['collections.id', 'collections.record_code', 'collections.title', 'collections.collection_type'])
                    ->map(fn (CollectionModel $collection) => [
                        'id' => $collection->id,
                        'record_code' => $collection->record_code,
                        'title' => $collection->title,
                        'collection_type' => $collection->collection_type,
                        'folder_name' => $collection->pivot->folder_name,
                    ])
                    ->values(),
            ],
        ];
    }

    private function guestLikeDashboard(User $user): array
    {
        return [
            'role' => 'user',
            'generated_at' => now(),
            'user' => $this->formatUser($user),
            'collections' => [
                'published_public' => CollectionModel::where('publication_status', 'published')
                    ->where('visibility', 'public')
                    ->count(),
                'library_public' => CollectionModel::where('unit_type', 'library')
                    ->where('publication_status', 'published')
                    ->where('visibility', 'public')
                    ->count(),
                'museum_public' => CollectionModel::where('unit_type', 'museum')
                    ->where('publication_status', 'published')
                    ->where('visibility', 'public')
                    ->count(),
            ],
        ];
    }

    private function countByStatus($query, string $column): array
    {
        return $query
            ->select($column, DB::raw('COUNT(*) as total'))
            ->groupBy($column)
            ->orderBy($column)
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) ($row->{$column} ?? 'unknown') => (int) $row->total,
            ])
            ->toArray();
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'user_type' => $user->user_type,
            'unit' => $user->unit,
            'roles' => $user->roles->pluck('name')->values(),
        ];
    }
}