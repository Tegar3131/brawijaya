<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection as CollectionModel;
use App\Models\User;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookmarkController extends Controller
{
    public function index(Request $request, BookmarkService $bookmarkService): JsonResponse
    {
        $member = $this->member($request);

        $filters = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'folder_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $bookmarks = $bookmarkService->list($member, $filters);

        return response()->json([
            'data' => $bookmarks->getCollection()
                ->map(fn (CollectionModel $collection) => $this->formatBookmark($collection))
                ->values(),
            'meta' => [
                'current_page' => $bookmarks->currentPage(),
                'from' => $bookmarks->firstItem(),
                'last_page' => $bookmarks->lastPage(),
                'per_page' => $bookmarks->perPage(),
                'to' => $bookmarks->lastItem(),
                'total' => $bookmarks->total(),
            ],
            'links' => [
                'first' => $bookmarks->url(1),
                'last' => $bookmarks->url($bookmarks->lastPage()),
                'prev' => $bookmarks->previousPageUrl(),
                'next' => $bookmarks->nextPageUrl(),
            ],
        ]);
    }

    public function store(Request $request, BookmarkService $bookmarkService): JsonResponse
    {
        $member = $this->member($request);

        $validated = $request->validate([
            'collection_id' => ['sometimes', 'nullable', 'integer', 'exists:collections,id'],
            'record_code' => ['sometimes', 'nullable', 'string', 'max:80'],
            'folder_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        if (empty($validated['collection_id']) && empty($validated['record_code'])) {
            return response()->json([
                'message' => 'collection_id atau record_code wajib diisi.',
            ], 422);
        }

        $collection = $this->resolveCollection($validated['collection_id'] ?? $validated['record_code']);

        $bookmark = $bookmarkService->save(
            member: $member,
            collection: $collection,
            folderName: $validated['folder_name'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Bookmark berhasil disimpan.',
            'data' => $this->formatBookmark($bookmark),
        ], 201);
    }

    public function update(Request $request, string $identifier, BookmarkService $bookmarkService): JsonResponse
    {
        $member = $this->member($request);

        $validated = $request->validate([
            'folder_name' => ['sometimes', 'nullable', 'string', 'max:120'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $collection = $this->resolveCollection($identifier);

        $bookmark = $bookmarkService->update(
            member: $member,
            collection: $collection,
            folderName: $validated['folder_name'] ?? null,
            notes: $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'Bookmark berhasil diperbarui.',
            'data' => $this->formatBookmark($bookmark),
        ]);
    }

    public function destroy(Request $request, string $identifier, BookmarkService $bookmarkService): JsonResponse
    {
        $member = $this->member($request);

        $collection = $this->resolveCollection($identifier);

        $bookmarkService->delete(
            member: $member,
            collection: $collection
        );

        return response()->json([
            'message' => 'Bookmark berhasil dihapus.',
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

    private function resolveCollection(string|int $identifier): CollectionModel
    {
        return CollectionModel::query()
            ->where(function ($query) use ($identifier) {
                if (is_numeric($identifier)) {
                    $query->where('id', (int) $identifier);
                }

                $query->orWhere('ulid', (string) $identifier)
                    ->orWhere('record_code', (string) $identifier);
            })
            ->firstOrFail();
    }

    private function formatBookmark(CollectionModel $collection): array
    {
        return [
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
            'creators' => $collection->creators->map(fn ($creator) => [
                'id' => $creator->id,
                'name' => $creator->name,
                'role' => $creator->pivot->role,
                'is_primary' => (bool) $creator->pivot->is_primary,
            ])->values(),
            'subjects' => $collection->subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'term' => $subject->term,
                'slug' => $subject->slug,
                'type' => $subject->type,
                'subject_type' => $subject->pivot->subject_type,
            ])->values(),
            'library' => $collection->libraryItem ? [
                'bibliographic_level' => $collection->libraryItem->bibliographic_level,
                'publisher_name' => $collection->libraryItem->publisher_name,
                'publication_year' => $collection->libraryItem->publication_year,
                'call_number' => $collection->libraryItem->call_number,
            ] : null,
            'museum' => $collection->museumItem ? [
                'inventory_number' => $collection->museumItem->inventory_number,
                'object_name' => $collection->museumItem->object_name,
                'object_type_label' => $collection->museumItem->object_type_label,
                'classification' => $collection->museumItem->classification,
            ] : null,
            'bookmark' => [
                'folder_name' => $collection->pivot->folder_name,
                'notes' => $collection->pivot->notes,
                'created_at' => $collection->pivot->created_at,
                'updated_at' => $collection->pivot->updated_at,
            ],
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