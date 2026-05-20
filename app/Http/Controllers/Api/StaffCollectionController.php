<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Collection as CollectionModel;
use App\Models\CollectionVersion;
use App\Models\User;
use App\Services\CollectionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffCollectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $staff = $this->staff($request);

        $filters = $request->validate([
            'q' => ['sometimes', 'nullable', 'string', 'max:255'],
            'unit_type' => ['sometimes', 'nullable', 'in:library,museum'],
            'collection_type' => ['sometimes', 'nullable', 'string', 'max:80'],
            'publication_status' => ['sometimes', 'nullable', 'in:draft,published,restricted,archived'],
            'visibility' => ['sometimes', 'nullable', 'in:public,member,internal,restricted'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'include_trashed' => ['sometimes', 'nullable', 'boolean'],
            'per_page' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = CollectionModel::query()
            ->with([
                'category',
                'currentLocation',
                'creators',
                'subjects',
                'primaryAsset',
                'libraryItem',
                'museumItem',
            ]);

        if (! empty($filters['include_trashed'])) {
            $query->withTrashed();
        }

        $this->applyStaffUnitScope($query, $staff);

        if (! empty($filters['q'])) {
            $keyword = '%' . str_replace(['%', '_'], ['\%', '\_'], $filters['q']) . '%';

            $query->where(function (Builder $where) use ($keyword) {
                $where->where('record_code', 'like', $keyword)
                    ->orWhere('title', 'like', $keyword)
                    ->orWhere('subtitle', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword);
            });
        }

        if (! empty($filters['unit_type'])) {
            $query->where('unit_type', $filters['unit_type']);
        }

        if (! empty($filters['collection_type'])) {
            $query->where('collection_type', $filters['collection_type']);
        }

        if (! empty($filters['publication_status'])) {
            $query->where('publication_status', $filters['publication_status']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);
        $perPage = max(1, min($perPage, 100));

        $result = $query
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();

        return response()->json([
            'data' => $result->getCollection()
                ->map(fn (CollectionModel $collection) => $this->formatCollectionCard($collection))
                ->values(),
            'meta' => [
                'current_page' => $result->currentPage(),
                'from' => $result->firstItem(),
                'last_page' => $result->lastPage(),
                'per_page' => $result->perPage(),
                'to' => $result->lastItem(),
                'total' => $result->total(),
            ],
            'links' => [
                'first' => $result->url(1),
                'last' => $result->url($result->lastPage()),
                'prev' => $result->previousPageUrl(),
                'next' => $result->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, string $identifier): JsonResponse
    {
        $staff = $this->staff($request);

        $collection = $this->resolveCollection($identifier);

        $this->authorizeCollectionUnit($staff, $collection);

        $collection->load([
            'category',
            'currentLocation',
            'creators',
            'subjects',
            'metadata.metadataElement',
            'digitalAssets',
            'primaryAsset',
            'libraryItem',
            'libraryCopies.location',
            'museumItem.materials',
            'museumItem.conditionReports.asset',
        ]);

        return response()->json([
            'data' => $this->formatCollectionDetail($collection),
        ]);
    }

    public function update(
        Request $request,
        string $identifier,
        \App\Services\LibraryCollectionService $libraryCollectionService,
        \App\Services\MuseumCollectionService $museumCollectionService
    ): JsonResponse {
        $staff = $this->staff($request);
        
        $collection = $this->resolveCollection($identifier);
        
        $this->authorizeCollectionUnit($staff, $collection);

        $payload = $request->all();
        
        $reason = $request->input('update_reason', 'Updated via staff unified form');

        if ($collection->unit_type === 'library') {
            $updated = $libraryCollectionService->update($collection, $payload, $staff, $reason);
        } else {
            $updated = $museumCollectionService->update($collection, $payload, $staff, $reason);
        }

        return response()->json([
            'success' => true,
            'message' => 'Koleksi berhasil diperbarui.',
            'data' => $this->formatCollectionDetail($updated),
        ]);
    }

    public function publish(
        Request $request,
        string $identifier,
        CollectionService $collectionService
    ): JsonResponse {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $updated = $collectionService->publish(
            collection: $collection,
            actor: $staff,
            reason: $validated['reason'] ?? 'Published by staff'
        );

        return response()->json([
            'message' => 'Koleksi berhasil dipublikasikan.',
            'data' => $this->formatCollectionCard($updated),
        ]);
    }

    public function archive(
        Request $request,
        string $identifier,
        CollectionService $collectionService
    ): JsonResponse {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $archived = $collectionService->archive(
            collection: $collection,
            reason: $validated['reason'],
            actor: $staff
        );

        return response()->json([
            'message' => 'Koleksi berhasil diarsipkan.',
            'data' => $this->formatCollectionCard($archived),
        ]);
    }

    public function restore(
        Request $request,
        string $identifier,
        CollectionService $collectionService
    ): JsonResponse {
        $staff = $this->staff($request);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $restored = $collectionService->restore(
            collection: $collection,
            actor: $staff,
            reason: $validated['reason'] ?? 'Restored by staff'
        );

        return response()->json([
            'message' => 'Koleksi berhasil dipulihkan.',
            'data' => $this->formatCollectionCard($restored),
        ]);
    }

    public function versions(Request $request, string $identifier): JsonResponse
    {
        $staff = $this->staff($request);

        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $versions = CollectionVersion::query()
            ->with('changedBy')
            ->where('collection_id', $collection->id)
            ->orderByDesc('version_no')
            ->get();

        return response()->json([
            'data' => $versions->map(fn (CollectionVersion $version) => [
                'id' => $version->id,
                'collection_id' => $version->collection_id,
                'version_no' => $version->version_no,
                'change_reason' => $version->change_reason,
                'snapshot_json' => $version->snapshot_json,
                'diff_json' => $version->diff_json,
                'created_at' => $version->created_at,
                'changed_by' => $version->changedBy ? [
                    'id' => $version->changedBy->id,
                    'name' => $version->changedBy->name,
                    'email' => $version->changedBy->email,
                ] : null,
            ])->values(),
        ]);
    }

    public function auditLogs(Request $request, string $identifier): JsonResponse
    {
        $staff = $this->staff($request);

        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $logs = AuditLog::query()
            ->with('user')
            ->where('auditable_type', CollectionModel::class)
            ->where('auditable_id', $collection->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'data' => $logs->map(fn (AuditLog $log) => [
                'id' => $log->id,
                'module' => $log->module,
                'action' => $log->action,
                'event' => $log->event,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
            ])->values(),
        ]);
    }

    private function staff(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');

        abort_if(
            ! $user->hasAnyRole(['admin', 'pustakawan', 'kurator']),
            403,
            'Endpoint ini hanya untuk admin, pustakawan, atau kurator.'
        );

        return $user;
    }

    private function applyStaffUnitScope(Builder $query, User $staff): void
    {
        if ($staff->hasRole('admin')) {
            return;
        }

        if ($staff->hasRole('pustakawan')) {
            $query->where('unit_type', 'library');
            return;
        }

        if ($staff->hasRole('kurator')) {
            $query->where('unit_type', 'museum');
        }
    }

    private function authorizeCollectionUnit(User $staff, CollectionModel $collection): void
    {
        if ($staff->hasRole('admin')) {
            return;
        }

        if ($staff->hasRole('pustakawan') && $collection->unit_type === 'library') {
            return;
        }

        if ($staff->hasRole('kurator') && $collection->unit_type === 'museum') {
            return;
        }

        abort(403, 'Anda tidak memiliki akses ke unit koleksi ini.');
    }

    private function resolveCollection(string $identifier): CollectionModel
    {
        return CollectionModel::withTrashed()
            ->where(function (Builder $query) use ($identifier) {
                if (is_numeric($identifier)) {
                    $query->where('id', (int) $identifier);
                }

                $query->orWhere('ulid', $identifier)
                    ->orWhere('record_code', $identifier);
            })
            ->firstOrFail();
    }

    private function formatCollectionCard(CollectionModel $collection): array
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
            'language_code' => $collection->language_code,
            'rights_status' => $collection->rights_status,
            'date_display' => $collection->date_display,
            'year_start' => $collection->year_start,
            'year_end' => $collection->year_end,
            'publication_status' => $collection->publication_status,
            'visibility' => $collection->visibility,
            'is_featured' => (bool) $collection->is_featured,
            'deleted_at' => $collection->deleted_at,
            'archived_at' => $collection->archived_at,
            'archived_reason' => $collection->archived_reason,
            'category' => $collection->category ? [
                'id' => $collection->category->id,
                'name' => $collection->category->name,
                'slug' => $collection->category->slug,
                'type' => $collection->category->type,
            ] : null,
            'current_location' => $collection->currentLocation ? [
                'id' => $collection->currentLocation->id,
                'code' => $collection->currentLocation->code,
                'name' => $collection->currentLocation->name,
                'location_type' => $collection->currentLocation->location_type,
            ] : null,
            'primary_asset' => $this->formatAsset($collection->primaryAsset),
            'creators_count' => $collection->relationLoaded('creators') ? $collection->creators->count() : null,
            'subjects_count' => $collection->relationLoaded('subjects') ? $collection->subjects->count() : null,
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
                'condition_current' => $collection->museumItem->condition_current,
            ] : null,
        ];
    }

    private function formatCollectionDetail(CollectionModel $collection): array
    {
        $data = $this->formatCollectionCard($collection);

        $data['creators'] = $collection->creators->map(fn ($creator) => [
            'id' => $creator->id,
            'name' => $creator->name,
            'role' => $creator->pivot->role,
            'is_primary' => (bool) $creator->pivot->is_primary,
            'sort_order' => $creator->pivot->sort_order,
        ])->values();

        $data['subjects'] = $collection->subjects->map(fn ($subject) => [
            'id' => $subject->id,
            'term' => $subject->term,
            'slug' => $subject->slug,
            'type' => $subject->type,
            'subject_type' => $subject->pivot->subject_type,
        ])->values();

        $data['metadata'] = $collection->metadata
            ->sortBy([
                fn ($a, $b) => strcmp($a->metadataElement?->standard ?? '', $b->metadataElement?->standard ?? ''),
                fn ($a, $b) => ($a->metadataElement?->sort_order ?? 0) <=> ($b->metadataElement?->sort_order ?? 0),
                fn ($a, $b) => $a->sort_order <=> $b->sort_order,
            ])
            ->map(fn ($metadata) => [
                'id' => $metadata->id,
                'standard' => $metadata->metadataElement?->standard,
                'element_key' => $metadata->metadataElement?->element_key,
                'label' => $metadata->metadataElement?->label,
                'value' => $metadata->display_value,
                'source' => $metadata->source,
                'sort_order' => $metadata->sort_order,
            ])
            ->values();

        $data['digital_assets'] = $collection->digitalAssets
            ->sortBy('sort_order')
            ->map(fn ($asset) => $this->formatAsset($asset))
            ->values();

        if ($collection->libraryItem) {
            $data['library']['isbn13'] = $collection->libraryItem->isbn13;
            $data['library']['isbn10'] = $collection->libraryItem->isbn10;
            $data['library']['issn'] = $collection->libraryItem->issn;
            $data['library']['doi'] = $collection->libraryItem->doi;
            $data['library']['copies'] = $collection->libraryCopies->map(fn ($copy) => [
                'id' => $copy->id,
                'copy_number' => $copy->copy_number,
                'barcode' => $copy->barcode,
                'status' => $copy->status,
                'condition_grade' => $copy->condition_grade,
                'location' => $copy->location ? [
                    'id' => $copy->location->id,
                    'code' => $copy->location->code,
                    'name' => $copy->location->name,
                ] : null,
            ])->values();
        }

        if ($collection->museumItem) {
            $data['museum']['maker_name'] = $collection->museumItem->maker_name;
            $data['museum']['culture'] = $collection->museumItem->culture;
            $data['museum']['period_display'] = $collection->museumItem->period_display;
            $data['museum']['material_summary'] = $collection->museumItem->material_summary;
            $data['museum']['technique_summary'] = $collection->museumItem->technique_summary;
            $data['museum']['provenance_history'] = $collection->museumItem->provenance_history;
            $data['museum']['materials'] = $collection->museumItem->materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
                'type' => $material->type,
                'is_primary' => (bool) $material->pivot->is_primary,
            ])->values();
            $data['museum']['condition_reports'] = $collection->museumItem->conditionReports->map(fn ($report) => [
                'id' => $report->id,
                'condition_grade' => $report->condition_grade,
                'inspected_at' => $report->inspected_at,
                'priority' => $report->priority,
                'next_review_at' => $report->next_review_at,
            ])->values();
        }

        return $data;
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
            'mime_type' => $asset->mime_type,
            'caption' => $asset->caption,
            'access_level' => $asset->access_level,
        ];
    }
}