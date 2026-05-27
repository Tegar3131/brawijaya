<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection as CollectionModel;
use App\Models\Creator;
use App\Models\DigitalAsset;
use App\Models\ItemMetadata;
use App\Models\Subject;
use App\Models\User;
use App\Services\CollectionMaintenanceService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffCollectionMaintenanceController extends Controller
{
    public function upsertMetadata(
        Request $request,
        string $identifier,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'metadata' => ['required', 'array', 'min:1'],
            'metadata.*.element_key' => ['required', 'exists:metadata_elements,element_key'],
            'metadata.*.value' => ['required'],
            'metadata.*.value_column' => [
                'sometimes',
                'nullable',
                'in:value_string,value_text,value_integer,value_decimal,value_date,value_datetime,value_json',
            ],
            'metadata.*.sort_order' => ['sometimes', 'nullable', 'integer'],
            'metadata.*.source' => ['sometimes', 'nullable', 'string', 'max:80'],
            'metadata.*.language_code' => ['sometimes', 'nullable', 'string', 'max:10'],
            'metadata.*.authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        $updated = $service->upsertMetadataRows(
            collection: $collection,
            rows: $validated['metadata'],
            actor: $staff,
            reason: $validated['reason'] ?? 'Update collection metadata via staff API'
        );

        return response()->json([
            'message' => 'Metadata koleksi berhasil diperbarui.',
            'data' => $this->formatSummary($updated),
        ]);
    }

    public function deleteMetadata(
        Request $request,
        string $identifier,
        ItemMetadata $metadata,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $updated = $service->deleteMetadata(
            collection: $collection,
            metadata: $metadata,
            actor: $staff,
            reason: $validated['reason'] ?? 'Delete metadata via staff API'
        );

        return response()->json([
            'message' => 'Metadata koleksi berhasil dihapus.',
            'data' => $this->formatSummary($updated),
        ]);
    }

    public function attachCreator(
        Request $request,
        string $identifier,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['sometimes', 'nullable', 'string', 'max:80'],
            'is_primary' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
            'authority_source' => ['sometimes', 'nullable', 'string', 'max:40'],
            'authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'birth_death_dates' => ['sometimes', 'nullable', 'string', 'max:120'],
            'biography' => ['sometimes', 'nullable', 'string'],
            'notes' => ['sometimes', 'nullable', 'string'],
        ]);

        $updated = $service->attachCreator(
            collection: $collection,
            data: $validated,
            actor: $staff,
            reason: $validated['reason'] ?? 'Attach creator via staff API'
        );

        return response()->json([
            'message' => 'Creator berhasil ditambahkan/diperbarui.',
            'data' => $this->formatSummary($updated),
        ]);
    }

    public function detachCreator(
        Request $request,
        string $identifier,
        Creator $creator,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $updated = $service->detachCreator(
            collection: $collection,
            creator: $creator,
            actor: $staff,
            reason: $validated['reason'] ?? 'Detach creator via staff API'
        );

        return response()->json([
            'message' => 'Creator berhasil dihapus dari koleksi.',
            'data' => $this->formatSummary($updated),
        ]);
    }

    public function attachSubject(
        Request $request,
        string $identifier,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'term' => ['required', 'string', 'max:255'],
            'vocabulary_source' => ['sometimes', 'nullable', 'string', 'max:40'],
            'authority_uri' => ['sometimes', 'nullable', 'string', 'max:500'],
            'type' => ['sometimes', 'nullable', 'in:topic,geographic,temporal,person,organization,event'],
            'subject_type' => ['sometimes', 'nullable', 'string', 'max:60'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
            'scope_note' => ['sometimes', 'nullable', 'string'],
        ]);

        $updated = $service->attachSubject(
            collection: $collection,
            data: $validated,
            actor: $staff,
            reason: $validated['reason'] ?? 'Attach subject via staff API'
        );

        return response()->json([
            'message' => 'Subject berhasil ditambahkan/diperbarui.',
            'data' => $this->formatSummary($updated),
        ]);
    }

    public function detachSubject(
        Request $request,
        string $identifier,
        Subject $subject,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $updated = $service->detachSubject(
            collection: $collection,
            subject: $subject,
            actor: $staff,
            reason: $validated['reason'] ?? 'Detach subject via staff API'
        );

        return response()->json([
            'message' => 'Subject berhasil dihapus dari koleksi.',
            'data' => $this->formatSummary($updated),
        ]);
    }

    public function registerDigitalAsset(
        Request $request,
        string $identifier,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'file' => ['sometimes', 'file', 'max:51200'],
            'directory' => ['sometimes', 'nullable', 'string', 'max:255'],

            'asset_type' => ['required', 'in:cover,photo,document,pdf,epub,video,audio,thumbnail,mets_package'],
            'file_role' => ['sometimes', 'nullable', 'in:original,access,thumbnail,watermarked,derivative'],
            'disk' => ['sometimes', 'nullable', 'string', 'max:60'],
            'path' => ['required_without:file', 'nullable', 'string', 'max:1000'],
            'public_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'watermarked_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'filename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'original_filename' => ['sometimes', 'nullable', 'string', 'max:255'],
            'mime_type' => ['required_without:file', 'nullable', 'string', 'max:120'],
            'extension' => ['sometimes', 'nullable', 'string', 'max:20'],
            'size_bytes' => ['sometimes', 'nullable', 'integer'],
            'checksum_sha256' => ['sometimes', 'nullable', 'string', 'max:64'],
            'width_px' => ['sometimes', 'nullable', 'integer'],
            'height_px' => ['sometimes', 'nullable', 'integer'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer'],
            'technical_metadata' => ['sometimes', 'nullable', 'array'],
            'captured_at' => ['sometimes', 'nullable', 'date'],
            'photographer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'view_angle' => ['sometimes', 'nullable', 'string', 'max:80'],
            'caption' => ['sometimes', 'nullable', 'string'],
            'is_primary' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'access_level' => ['sometimes', 'nullable', 'in:public,member,internal,restricted'],
            'watermark_applied' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
        ]);

        $assetData = collect($validated)
            ->except(['file', 'directory', 'reason'])
            ->toArray();

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $disk = $assetData['disk'] ?? 'public';
            $directory = $validated['directory'] ?? 'digital-assets/' . $collection->unit_type;

            $storedPath = $file->store($directory, $disk);

            $assetData['disk'] = $disk;
            $assetData['path'] = $storedPath;
            $assetData['filename'] = basename($storedPath);
            $assetData['original_filename'] = $file->getClientOriginalName();
            $assetData['mime_type'] = $file->getClientMimeType();
            $assetData['extension'] = $file->getClientOriginalExtension();
            $assetData['size_bytes'] = $file->getSize();
            $assetData['checksum_sha256'] = hash_file('sha256', $file->getRealPath());
        }

        $asset = $service->registerDigitalAsset(
            collection: $collection,
            data: $assetData,
            actor: $staff,
            reason: $validated['reason'] ?? 'Register digital asset via staff API'
        );

        return response()->json([
            'message' => 'Digital asset berhasil diregistrasikan.',
            'data' => $this->formatAsset($asset),
        ], 201);
    }

    public function updateDigitalAsset(
        Request $request,
        string $identifier,
        DigitalAsset $asset,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'asset_type' => ['sometimes', 'in:cover,photo,document,pdf,epub,video,audio,thumbnail,mets_package'],
            'file_role' => ['sometimes', 'nullable', 'in:original,access,thumbnail,watermarked,derivative'],
            'disk' => ['sometimes', 'nullable', 'string', 'max:60'],
            'public_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'caption' => ['sometimes', 'nullable', 'string'],
            'is_primary' => ['sometimes', 'boolean'],
            'is_public' => ['sometimes', 'boolean'],
            'access_level' => ['sometimes', 'nullable', 'in:public,member,internal,restricted'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
        ]);

        $assetData = collect($validated)->except(['reason'])->toArray();

        $asset = $service->updateDigitalAsset(
            collection: $collection,
            asset: $asset,
            data: $assetData,
            actor: $staff,
            reason: $validated['reason'] ?? 'Update digital asset via staff API'
        );

        return response()->json([
            'message' => 'Digital asset berhasil diperbarui.',
            'data' => $this->formatAsset($asset),
        ]);
    }

    public function deleteDigitalAsset(
        Request $request,
        string $identifier,
        DigitalAsset $asset,
        CollectionMaintenanceService $service
    ): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validate([
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        $updated = $service->deleteDigitalAsset(
            collection: $collection,
            asset: $asset,
            actor: $staff,
            reason: $validated['reason'] ?? 'Delete digital asset via staff API'
        );

        return response()->json([
            'message' => 'Digital asset berhasil dihapus.',
            'data' => $this->formatSummary($updated),
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

    private function formatSummary(CollectionModel $collection): array
    {
        $collection->loadMissing([
            'metadata.metadataElement',
            'creators',
            'subjects',
            'digitalAssets',
            'versions',
        ]);

        return [
            'id' => $collection->id,
            'ulid' => $collection->ulid,
            'record_code' => $collection->record_code,
            'unit_type' => $collection->unit_type,
            'collection_type' => $collection->collection_type,
            'title' => $collection->title,
            'metadata_count' => $collection->metadata->count(),
            'creators_count' => $collection->creators->count(),
            'subjects_count' => $collection->subjects->count(),
            'digital_assets_count' => $collection->digitalAssets->count(),
            'versions_count' => $collection->versions->count(),
            'metadata' => $collection->metadata->map(fn ($metadata) => [
                'id' => $metadata->id,
                'standard' => $metadata->metadataElement?->standard,
                'element_key' => $metadata->metadataElement?->element_key,
                'label' => $metadata->metadataElement?->label,
                'value' => $metadata->display_value,
                'sort_order' => $metadata->sort_order,
                'source' => $metadata->source,
            ])->values(),
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
                'sort_order' => $subject->pivot->sort_order,
            ])->values(),
            'digital_assets' => $collection->digitalAssets
                ->sortBy('sort_order')
                ->map(fn ($asset) => $this->formatAsset($asset))
                ->values(),
        ];
    }

    private function formatAsset(?DigitalAsset $asset): ?array
    {
        if (! $asset) {
            return null;
        }

        return [
            'id' => $asset->id,
            'ulid' => $asset->ulid,
            'collection_id' => $asset->collection_id,
            'asset_type' => $asset->asset_type,
            'file_role' => $asset->file_role,
            'disk' => $asset->disk,
            'path' => $asset->path,
            'thumbnail_path' => $asset->thumbnail_path,
            'watermarked_path' => $asset->watermarked_path,
            'filename' => $asset->filename,
            'original_filename' => $asset->original_filename,
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