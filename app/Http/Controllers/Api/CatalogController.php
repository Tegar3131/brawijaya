<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection as CollectionModel;
use App\Models\User;
use App\Services\CollectionSearchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\Api\Catalog\CatalogSearchRequest;

class CatalogController extends Controller
{
    public function search(CatalogSearchRequest $request, CollectionSearchService $searchService): JsonResponse
    {
        $filters = $this->normalizeFilters($request);



        $result = $searchService->search(
            filters: $filters,
            user: $request->user(),
            ipAddress: $request->ip()
        );

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
        $query = CollectionModel::query()
            ->with([
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

        $this->applyAccessScope($query, $request->user());

        $collection = $query
            ->where(function (Builder $where) use ($identifier) {
                $where->where('ulid', $identifier)
                    ->orWhere('record_code', $identifier);
            })
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatCollectionDetail($collection),
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        $type = $request->query('type');

        $query = Category::query()
            ->where('is_active', true)
            ->when($type, fn (Builder $q) => $q->where('type', $type))
            ->withCount([
                'collections as collections_count' => function (Builder $collectionQuery) use ($request) {
                    $this->applyAccessScope($collectionQuery, $request->user());
                },
            ])
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name');

        return response()->json([
            'data' => $query->get()->map(fn (Category $category) => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => $category->type,
                'description' => $category->description,
                'sort_order' => $category->sort_order,
                'collections_count' => $category->collections_count,
            ])->values(),
        ]);
    }

    public function category(Request $request, string $slug, CollectionSearchService $searchService): JsonResponse
    {
        $category = Category::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $filters = $this->normalizeFilters($request);
        $filters['category_slug'] = $slug;

        $result = $searchService->search(
            filters: $filters,
            user: $request->user(),
            ipAddress: $request->ip()
        );

        return response()->json([
            'category' => [
                'id' => $category->id,
                'parent_id' => $category->parent_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'type' => $category->type,
                'description' => $category->description,
            ],
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

    private function applyAccessScope(Builder $query, ?User $user): void
    {
        if ($user?->hasAnyRole(['admin', 'pustakawan', 'kurator'])) {
            return;
        }

        $query->where('publication_status', 'published');

        if ($user?->hasRole('member')) {
            $query->whereIn('visibility', ['public', 'member']);
            return;
        }

        $query->where('visibility', 'public');
    }

    private function normalizeFilters(Request $request): array
    {
        $filters = $request->only([
            'q',
            'unit_type',
            'collection_type',
            'category_id',
            'category_slug',
            'year_from',
            'year_to',
            'subject',
            'subject_id',
            'creator',
            'creator_id',
            'publication_status',
            'visibility',
            'is_featured',
            'language_code',
            'sort',
            'per_page',
            'include_trashed',
        ]);

        foreach (['category_id', 'year_from', 'year_to', 'subject_id', 'creator_id', 'per_page'] as $integerKey) {
            if (array_key_exists($integerKey, $filters) && $filters[$integerKey] !== null && $filters[$integerKey] !== '') {
                $filters[$integerKey] = (int) $filters[$integerKey];
            }
        }

        foreach (['is_featured', 'include_trashed'] as $booleanKey) {
            if (array_key_exists($booleanKey, $filters)) {
                $filters[$booleanKey] = filter_var($filters[$booleanKey], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
        }

        return array_filter($filters, fn ($value) => $value !== null && $value !== '');
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
                'is_public' => (bool) $collection->currentLocation->is_public,
            ] : null,
            'creators' => $collection->creators->map(fn ($creator) => [
                'id' => $creator->id,
                'name' => $creator->name,
                'role' => $creator->pivot->role,
                'is_primary' => (bool) $creator->pivot->is_primary,
                'sort_order' => $creator->pivot->sort_order,
            ])->values(),
            'subjects' => $collection->subjects->map(fn ($subject) => [
                'id' => $subject->id,
                'term' => $subject->term,
                'slug' => $subject->slug,
                'type' => $subject->type,
                'subject_type' => $subject->pivot->subject_type,
            ])->values(),
            'primary_asset' => $this->formatAsset($collection->primaryAsset),
            'library' => $collection->libraryItem ? [
                'bibliographic_level' => $collection->libraryItem->bibliographic_level,
                'publisher_name' => $collection->libraryItem->publisher_name,
                'publication_year' => $collection->libraryItem->publication_year,
                'ddc_classification' => $collection->libraryItem->ddc_classification,
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
        $card = $this->formatCollectionCard($collection);

        $card['metadata'] = $collection->metadata
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
                'data_type' => $metadata->metadataElement?->data_type,
                'value' => $metadata->display_value,
                'language_code' => $metadata->language_code,
                'authority_uri' => $metadata->authority_uri,
                'sort_order' => $metadata->sort_order,
                'source' => $metadata->source,
            ])
            ->values();

        $card['digital_assets'] = $collection->digitalAssets
            ->sortBy('sort_order')
            ->map(fn ($asset) => $this->formatAsset($asset))
            ->values();

        if ($collection->libraryItem) {
            $card['library']['isbn13'] = $collection->libraryItem->isbn13;
            $card['library']['isbn10'] = $collection->libraryItem->isbn10;
            $card['library']['issn'] = $collection->libraryItem->issn;
            $card['library']['doi'] = $collection->libraryItem->doi;
            $card['library']['publisher_place'] = $collection->libraryItem->publisher_place;
            $card['library']['edition'] = $collection->libraryItem->edition;
            $card['library']['physical_extent'] = $collection->libraryItem->physical_extent;
            $card['library']['physical_dimensions'] = $collection->libraryItem->physical_dimensions;
            $card['library']['pages'] = $collection->libraryItem->pages;
            $card['library']['availability_status'] = $collection->libraryItem->availability_status;
            $card['library']['copies'] = $collection->libraryCopies->map(fn ($copy) => [
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
            ])->values();
        }

        if ($collection->museumItem) {
            $card['museum']['maker_name'] = $collection->museumItem->maker_name;
            $card['museum']['culture'] = $collection->museumItem->culture;
            $card['museum']['period_display'] = $collection->museumItem->period_display;
            $card['museum']['material_summary'] = $collection->museumItem->material_summary;
            $card['museum']['technique_summary'] = $collection->museumItem->technique_summary;
            $card['museum']['dimensions'] = [
                'height_cm' => $collection->museumItem->height_cm,
                'width_cm' => $collection->museumItem->width_cm,
                'length_depth_cm' => $collection->museumItem->length_depth_cm,
                'weight_gram' => $collection->museumItem->weight_gram,
            ];
            $card['museum']['condition_notes'] = $collection->museumItem->condition_notes;
            $card['museum']['provenance_history'] = $collection->museumItem->provenance_history;
            $card['museum']['acquisition_method'] = $collection->museumItem->acquisition_method;
            $card['museum']['acquisition_source'] = $collection->museumItem->acquisition_source;
            $card['museum']['acquisition_date'] = $collection->museumItem->acquisition_date;
            $card['museum']['is_sensitive'] = (bool) $collection->museumItem->is_sensitive;
            $card['museum']['materials'] = $collection->museumItem->materials->map(fn ($material) => [
                'id' => $material->id,
                'name' => $material->name,
                'name_en' => $material->name_en,
                'type' => $material->type,
                'authority_uri' => $material->authority_uri,
                'is_primary' => (bool) $material->pivot->is_primary,
            ])->values();
            $card['museum']['condition_reports'] = $collection->museumItem->conditionReports->map(fn ($report) => [
                'id' => $report->id,
                'condition_grade' => $report->condition_grade,
                'inspected_at' => $report->inspected_at,
                'description' => $report->description,
                'recommendation' => $report->recommendation,
                'priority' => $report->priority,
                'next_review_at' => $report->next_review_at,
                'asset' => $this->formatAsset($report->asset),
            ])->values();
        }

        return $card;
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
            'disk' => $asset->disk,
            'path' => $asset->path,
            'public_url' => $asset->public_url,
            'thumbnail_path' => $asset->thumbnail_path,
            'watermarked_path' => $asset->watermarked_path,
            'filename' => $asset->filename,
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'size_bytes' => $asset->size_bytes,
            'caption' => $asset->caption,
            'is_primary' => (bool) $asset->is_primary,
            'is_public' => (bool) $asset->is_public,
            'access_level' => $asset->access_level,
        ];
    }
}