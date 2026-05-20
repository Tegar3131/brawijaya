<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Collection as CollectionModel;
use App\Models\DigitalAsset;
use App\Models\User;
use App\Services\DigitalAssetFileService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use App\Http\Requests\Api\Staff\DigitalAssetUploadRequest;

class StaffDigitalAssetUploadController extends Controller
{
    public function upload(
    DigitalAssetUploadRequest $request,
    string $identifier,
    DigitalAssetFileService $digitalAssetFileService
): JsonResponse {
        $staff = $this->staff($request);
        $collection = $this->resolveCollection($identifier);
        $this->authorizeCollectionUnit($staff, $collection);

        $validated = $request->validated();

        try {
            $asset = $digitalAssetFileService->upload(
                collection: $collection,
                file: $request->file('file'),
                data: $validated,
                actor: $staff,
                reason: $validated['reason'] ?? 'Upload digital asset file via staff API'
            );

            return response()->json([
                'message' => 'File digital asset berhasil diupload.',
                'data' => $this->formatAsset($asset),
            ], 201);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
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

    private function formatAsset(DigitalAsset $asset): array
    {
        return [
            'id' => $asset->id,
            'ulid' => $asset->ulid,
            'collection_id' => $asset->collection_id,
            'uploaded_by' => $asset->uploaded_by,
            'asset_type' => $asset->asset_type,
            'file_role' => $asset->file_role,
            'disk' => $asset->disk,
            'path' => $asset->path,
            'public_url' => $asset->public_url,
            'thumbnail_path' => $asset->thumbnail_path,
            'watermarked_path' => $asset->watermarked_path,
            'filename' => $asset->filename,
            'original_filename' => $asset->original_filename,
            'mime_type' => $asset->mime_type,
            'extension' => $asset->extension,
            'size_bytes' => $asset->size_bytes,
            'checksum_sha256' => $asset->checksum_sha256,
            'width_px' => $asset->width_px,
            'height_px' => $asset->height_px,
            'technical_metadata' => $asset->technical_metadata,
            'caption' => $asset->caption,
            'is_primary' => (bool) $asset->is_primary,
            'is_public' => (bool) $asset->is_public,
            'access_level' => $asset->access_level,
            'watermark_applied' => (bool) $asset->watermark_applied,
            'sort_order' => $asset->sort_order,
        ];
    }
}