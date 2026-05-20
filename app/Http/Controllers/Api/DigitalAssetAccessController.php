<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DigitalAsset;
use App\Services\DigitalAssetAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class DigitalAssetAccessController extends Controller
{
    public function download(
        Request $request,
        DigitalAsset $asset,
        DigitalAssetAccessService $accessService
    ) {
        try {
            $accessService->assertCanDownload($asset, $request->user());
        } catch (RuntimeException $exception) {
            abort(403, $exception->getMessage());
        }

        if (! Storage::disk($asset->disk)->exists($asset->path)) {
            abort(404, 'File tidak ditemukan di storage.');
        }

        $downloadName = $asset->original_filename ?: $asset->filename;

        return Storage::disk($asset->disk)->download(
            $asset->path,
            $downloadName,
            [
                'Content-Type' => $asset->mime_type,
            ]
        );
    }
}