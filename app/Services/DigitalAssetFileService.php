<?php

namespace App\Services;

use App\Models\Collection as CollectionModel;
use App\Models\DigitalAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class DigitalAssetFileService
{
    public function __construct(
        private readonly CollectionMaintenanceService $collectionMaintenanceService
    ) {
    }

    public function upload(
        CollectionModel $collection,
        UploadedFile $file,
        array $data,
        User $actor,
        ?string $reason = 'Upload digital asset file'
    ): DigitalAsset {
        if ($collection->trashed()) {
            throw new RuntimeException('Tidak bisa upload asset ke koleksi yang sedang diarsipkan.');
        }

        $assetType = $data['asset_type'];
        $this->validateMimeForAssetType($file, $assetType);

        $disk = $data['disk'] ?? 'public';
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $originalName = $file->getClientOriginalName();
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');

        $directory = $this->directoryFor($collection, $assetType, $data['directory'] ?? null);
        $filename = $this->safeFilename($collection, $file, $extension);

        $checksum = hash_file('sha256', $file->getRealPath());
        [$width, $height] = $this->imageDimensions($file);

        $storedPath = null;
        $thumbnailPath = null;

        try {
            $storedPath = $file->storeAs($directory, $filename, $disk);

            if ($this->isImageMime($mimeType)) {
                $thumbnailPath = $data['thumbnail_path'] ?? $this->writeThumbnailPlaceholder(
                    disk: $disk,
                    directory: $directory,
                    title: $collection->title,
                    assetType: $assetType
                );
            }

            $technicalMetadata = $data['technical_metadata'] ?? [];

            $technicalMetadata['upload'] = [
                'uploaded_at' => now()->toIso8601String(),
                'original_filename' => $originalName,
                'stored_filename' => $filename,
                'stored_path' => $storedPath,
                'mime_type' => $mimeType,
                'size_bytes' => $file->getSize(),
                'checksum_sha256' => $checksum,
            ];

            if ($width !== null && $height !== null) {
                $technicalMetadata['mix'] = array_merge(
                    $technicalMetadata['mix'] ?? [],
                    [
                        'imageWidth' => $width,
                        'imageLength' => $height,
                    ]
                );
            }

            $technicalMetadata['thumbnail'] = [
                'status' => $thumbnailPath ? 'placeholder' : 'not_applicable',
                'path' => $thumbnailPath,
                'note' => $thumbnailPath
                    ? 'Placeholder SVG. Real thumbnail generation will be implemented later.'
                    : null,
            ];

            return $this->collectionMaintenanceService->registerDigitalAsset(
                collection: $collection,
                data: [
                    'uploaded_by' => $actor->id,
                    'asset_type' => $assetType,
                    'file_role' => $data['file_role'] ?? 'original',
                    'disk' => $disk,
                    'path' => $storedPath,
                    'public_url' => $data['public_url'] ?? null,
                    'thumbnail_path' => $thumbnailPath,
                    'watermarked_path' => $data['watermarked_path'] ?? null,
                    'filename' => $filename,
                    'original_filename' => $originalName,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                    'size_bytes' => $file->getSize(),
                    'checksum_sha256' => $checksum,
                    'width_px' => $width,
                    'height_px' => $height,
                    'duration_seconds' => $data['duration_seconds'] ?? null,
                    'technical_metadata' => $technicalMetadata,
                    'captured_at' => $data['captured_at'] ?? null,
                    'photographer_name' => $data['photographer_name'] ?? null,
                    'view_angle' => $data['view_angle'] ?? null,
                    'caption' => $data['caption'] ?? null,
                    'is_primary' => $data['is_primary'] ?? false,
                    'is_public' => $data['is_public'] ?? true,
                    'access_level' => $data['access_level'] ?? 'public',
                    'watermark_applied' => $data['watermark_applied'] ?? false,
                    'sort_order' => $data['sort_order'] ?? 0,
                ],
                actor: $actor,
                reason: $reason
            );
        } catch (Throwable $exception) {
            if ($storedPath) {
                Storage::disk($disk)->delete($storedPath);
            }

            if ($thumbnailPath) {
                Storage::disk($disk)->delete($thumbnailPath);
            }

            throw $exception;
        }
    }

    private function validateMimeForAssetType(UploadedFile $file, string $assetType): void
    {
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();

        $allowed = [
            'cover' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'photo' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
            'thumbnail' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml'],
            'document' => [
                'application/pdf',
                'text/plain',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'pdf' => ['application/pdf'],
            'epub' => ['application/epub+zip', 'application/zip'],
            'video' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
            'audio' => ['audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/ogg'],
            'mets_package' => ['application/xml', 'text/xml', 'application/zip'],
        ];

        if (! isset($allowed[$assetType])) {
            throw ValidationException::withMessages([
                'asset_type' => ['asset_type tidak dikenali.'],
            ]);
        }

        if (! in_array($mimeType, $allowed[$assetType], true)) {
            throw ValidationException::withMessages([
                'file' => [
                    "MIME file {$mimeType} tidak sesuai untuk asset_type {$assetType}.",
                ],
            ]);
        }
    }

    private function directoryFor(CollectionModel $collection, string $assetType, ?string $directory = null): string
    {
        if ($directory) {
            return trim($directory, '/');
        }

        $safeRecordCode = Str::slug($collection->record_code);

        return "digital-assets/{$collection->unit_type}/{$safeRecordCode}/{$assetType}";
    }

    private function safeFilename(CollectionModel $collection, UploadedFile $file, string $extension): string
    {
        $baseName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeBaseName = Str::slug($baseName) ?: 'asset';
        $safeRecordCode = Str::slug($collection->record_code);
        $ulid = Str::lower((string) Str::ulid());

        return "{$safeRecordCode}-{$ulid}-{$safeBaseName}.{$extension}";
    }

    private function imageDimensions(UploadedFile $file): array
    {
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();

        if (! $this->isImageMime($mimeType)) {
            return [null, null];
        }

        $size = @getimagesize($file->getRealPath());

        if (! $size) {
            return [null, null];
        }

        return [
            $size[0] ?? null,
            $size[1] ?? null,
        ];
    }

    private function isImageMime(?string $mimeType): bool
    {
        return in_array($mimeType, [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/svg+xml',
        ], true);
    }

    private function writeThumbnailPlaceholder(
        string $disk,
        string $directory,
        string $title,
        string $assetType
    ): string {
        $path = trim($directory, '/') . '/thumbnails/' . Str::lower((string) Str::ulid()) . '-placeholder.svg';

        $safeTitle = htmlspecialchars(Str::limit($title, 40), ENT_QUOTES, 'UTF-8');
        $safeType = htmlspecialchars($assetType, ENT_QUOTES, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="480" height="320" viewBox="0 0 480 320">
  <rect width="480" height="320" fill="#f3f4f6"/>
  <rect x="24" y="24" width="432" height="272" rx="18" fill="#ffffff" stroke="#d1d5db"/>
  <text x="240" y="145" text-anchor="middle" font-family="Arial, sans-serif" font-size="22" fill="#374151">SIMPB</text>
  <text x="240" y="178" text-anchor="middle" font-family="Arial, sans-serif" font-size="16" fill="#6b7280">{$safeType} placeholder</text>
  <text x="240" y="210" text-anchor="middle" font-family="Arial, sans-serif" font-size="14" fill="#9ca3af">{$safeTitle}</text>
</svg>
SVG;

        Storage::disk($disk)->put($path, $svg);

        return $path;
    }
}