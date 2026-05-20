<?php

namespace App\Services;

use App\Models\Collection as CollectionModel;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BookmarkService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function list(User $member, array $filters = []): LengthAwarePaginator
    {
        $this->assertMember($member);

        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = max(1, min($perPage, 50));

        $query = $member->bookmarks()
            ->with([
                'category',
                'primaryAsset',
                'creators',
                'subjects',
                'libraryItem',
                'museumItem',
            ])
            ->where('collections.publication_status', 'published')
            ->whereIn('collections.visibility', ['public', 'member'])
            ->when(! empty($filters['folder_name']), function (Builder $q) use ($filters) {
                $q->where('user_bookmarks.folder_name', $filters['folder_name']);
            })
            ->when(! empty($filters['q']), function (Builder $q) use ($filters) {
                $keyword = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['q']) . '%';

                $q->where(function (Builder $where) use ($keyword) {
                    $where->where('collections.record_code', 'like', $keyword)
                        ->orWhere('collections.title', 'like', $keyword)
                        ->orWhere('collections.subtitle', 'like', $keyword)
                        ->orWhere('collections.description', 'like', $keyword);
                });
            })
            ->orderByDesc('user_bookmarks.created_at');

        return $query->paginate($perPage)->withQueryString();
    }

    public function save(
        User $member,
        CollectionModel $collection,
        ?string $folderName = null,
        ?string $notes = null
    ): CollectionModel {
        return DB::transaction(function () use ($member, $collection, $folderName, $notes) {
            $this->assertMember($member);
            $this->assertCollectionBookmarkable($collection);

            $exists = $member->bookmarks()
                ->where('collections.id', $collection->id)
                ->exists();

            $pivotData = [
                'folder_name' => $folderName,
                'notes' => $notes,
                'updated_at' => now(),
            ];

            if ($exists) {
                $oldPivot = DB::table('user_bookmarks')
                    ->where('user_id', $member->id)
                    ->where('collection_id', $collection->id)
                    ->first();

                $member->bookmarks()->updateExistingPivot($collection->id, $pivotData);

                $event = 'bookmark.updated';
                $action = 'bookmark_update';
                $oldValues = [
                    'folder_name' => $oldPivot?->folder_name,
                    'notes' => $oldPivot?->notes,
                ];
            } else {
                $member->bookmarks()->attach($collection->id, $pivotData + [
                    'created_at' => now(),
                ]);

                $event = 'bookmark.created';
                $action = 'bookmark_create';
                $oldValues = null;
            }

            $fresh = $member->bookmarks()
                ->with([
                    'category',
                    'primaryAsset',
                    'creators',
                    'subjects',
                    'libraryItem',
                    'museumItem',
                ])
                ->where('collections.id', $collection->id)
                ->firstOrFail();

            $this->auditLogService->record(
                module: 'member',
                action: $action,
                event: $event,
                actor: $member,
                auditable: $collection,
                oldValues: $oldValues,
                newValues: [
                    'user_id' => $member->id,
                    'collection_id' => $collection->id,
                    'folder_name' => $folderName,
                    'notes' => $notes,
                ],
                metadata: [
                    'record_code' => $collection->record_code,
                    'title' => $collection->title,
                ]
            );

            return $fresh;
        });
    }

    public function update(
        User $member,
        CollectionModel $collection,
        ?string $folderName = null,
        ?string $notes = null
    ): CollectionModel {
        return DB::transaction(function () use ($member, $collection, $folderName, $notes) {
            $this->assertMember($member);

            $oldPivot = DB::table('user_bookmarks')
                ->where('user_id', $member->id)
                ->where('collection_id', $collection->id)
                ->first();

            if (! $oldPivot) {
                throw new RuntimeException('Bookmark tidak ditemukan.');
            }

            $member->bookmarks()->updateExistingPivot($collection->id, [
                'folder_name' => $folderName,
                'notes' => $notes,
                'updated_at' => now(),
            ]);

            $fresh = $member->bookmarks()
                ->with([
                    'category',
                    'primaryAsset',
                    'creators',
                    'subjects',
                    'libraryItem',
                    'museumItem',
                ])
                ->where('collections.id', $collection->id)
                ->firstOrFail();

            $this->auditLogService->record(
                module: 'member',
                action: 'bookmark_update',
                event: 'bookmark.updated',
                actor: $member,
                auditable: $collection,
                oldValues: [
                    'folder_name' => $oldPivot->folder_name,
                    'notes' => $oldPivot->notes,
                ],
                newValues: [
                    'folder_name' => $folderName,
                    'notes' => $notes,
                ],
                metadata: [
                    'record_code' => $collection->record_code,
                    'title' => $collection->title,
                ]
            );

            return $fresh;
        });
    }

    public function delete(User $member, CollectionModel $collection): void
    {
        DB::transaction(function () use ($member, $collection) {
            $this->assertMember($member);

            $oldPivot = DB::table('user_bookmarks')
                ->where('user_id', $member->id)
                ->where('collection_id', $collection->id)
                ->first();

            if (! $oldPivot) {
                throw new RuntimeException('Bookmark tidak ditemukan.');
            }

            $member->bookmarks()->detach($collection->id);

            $this->auditLogService->record(
                module: 'member',
                action: 'bookmark_delete',
                event: 'bookmark.deleted',
                actor: $member,
                auditable: $collection,
                oldValues: [
                    'user_id' => $member->id,
                    'collection_id' => $collection->id,
                    'folder_name' => $oldPivot->folder_name,
                    'notes' => $oldPivot->notes,
                ],
                newValues: null,
                metadata: [
                    'record_code' => $collection->record_code,
                    'title' => $collection->title,
                ]
            );
        });
    }

    private function assertMember(User $member): void
    {
        if (! $member->hasRole('member')) {
            throw new RuntimeException('Endpoint ini hanya untuk member.');
        }

        if (! $member->hasActiveMembership()) {
            throw new RuntimeException('Membership tidak aktif.');
        }
    }

    private function assertCollectionBookmarkable(CollectionModel $collection): void
    {
        if ($collection->publication_status !== 'published') {
            throw new RuntimeException('Koleksi belum dipublikasikan.');
        }

        if (! in_array($collection->visibility, ['public', 'member'], true)) {
            throw new RuntimeException('Koleksi tidak dapat di-bookmark oleh member.');
        }
    }
}