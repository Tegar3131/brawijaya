<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection as CollectionModel;
use App\Models\User;
use App\Services\LibraryCollectionService;
use App\Services\MuseumCollectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class StaffCollectionCreateController extends Controller
{
    public function storeLibrary(
        Request $request,
        LibraryCollectionService $libraryCollectionService
    ): JsonResponse {
        $staff = $this->libraryStaff($request);

        try {
            $collection = $libraryCollectionService->create(
                data: $request->all(),
                actor: $staff,
                reason: $request->input('reason', 'Created library collection via staff API')
            );

            return response()->json([
                'message' => 'Koleksi perpustakaan berhasil dibuat.',
                'data' => $this->formatCreatedCollection($collection),
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function storeMuseum(
        Request $request,
        MuseumCollectionService $museumCollectionService
    ): JsonResponse {
        $staff = $this->museumStaff($request);

        try {
            $collection = $museumCollectionService->create(
                data: $request->all(),
                actor: $staff,
                reason: $request->input('reason', 'Created museum collection via staff API')
            );

            return response()->json([
                'message' => 'Koleksi museum berhasil dibuat.',
                'data' => $this->formatCreatedCollection($collection),
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function libraryStaff(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');

        abort_if(
            ! $user->hasAnyRole(['admin', 'pustakawan']),
            403,
            'Endpoint create koleksi perpustakaan hanya untuk admin atau pustakawan.'
        );

        return $user;
    }

    private function museumStaff(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        abort_if(! $user, 401, 'Unauthenticated.');

        abort_if(
            ! $user->hasAnyRole(['admin', 'kurator']),
            403,
            'Endpoint create koleksi museum hanya untuk admin atau kurator.'
        );

        return $user;
    }

    private function formatCreatedCollection(CollectionModel $collection): array
    {
        $collection->loadMissing([
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
            'versions',
        ]);

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
            'creators_count' => $collection->creators->count(),
            'subjects_count' => $collection->subjects->count(),
            'metadata_count' => $collection->metadata->count(),
            'digital_assets_count' => $collection->digitalAssets->count(),
            'versions_count' => $collection->versions->count(),
            'primary_asset' => $this->formatAsset($collection->primaryAsset),
            'library' => $collection->libraryItem ? [
                'id' => $collection->libraryItem->id,
                'bibliographic_level' => $collection->libraryItem->bibliographic_level,
                'isbn13' => $collection->libraryItem->isbn13,
                'isbn10' => $collection->libraryItem->isbn10,
                'issn' => $collection->libraryItem->issn,
                'doi' => $collection->libraryItem->doi,
                'publisher_name' => $collection->libraryItem->publisher_name,
                'publisher_place' => $collection->libraryItem->publisher_place,
                'publication_year' => $collection->libraryItem->publication_year,
                'ddc_classification' => $collection->libraryItem->ddc_classification,
                'call_number' => $collection->libraryItem->call_number,
                'copies_count' => $collection->libraryCopies->count(),
            ] : null,
            'museum' => $collection->museumItem ? [
                'id' => $collection->museumItem->id,
                'inventory_number' => $collection->museumItem->inventory_number,
                'object_name' => $collection->museumItem->object_name,
                'object_type_label' => $collection->museumItem->object_type_label,
                'classification' => $collection->museumItem->classification,
                'condition_current' => $collection->museumItem->condition_current,
                'materials_count' => $collection->museumItem->materials->count(),
                'condition_reports_count' => $collection->museumItem->conditionReports->count(),
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
            'mime_type' => $asset->mime_type,
            'caption' => $asset->caption,
            'access_level' => $asset->access_level,
        ];
    }
}