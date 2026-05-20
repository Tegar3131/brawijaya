<?php

namespace App\Http\Requests\Api\Staff;

use Illuminate\Foundation\Http\FormRequest;

class DigitalAssetUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasAnyRole(['admin', 'pustakawan', 'kurator']);
    }

    public function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:102400'],
            'directory' => ['sometimes', 'nullable', 'string', 'max:255'],

            'asset_type' => ['required', 'in:cover,photo,document,pdf,epub,video,audio,thumbnail,mets_package'],
            'file_role' => ['sometimes', 'nullable', 'in:original,access,thumbnail,watermarked,derivative'],
            'disk' => ['sometimes', 'nullable', 'string', 'max:60'],
            'public_url' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'thumbnail_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'watermarked_path' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'duration_seconds' => ['sometimes', 'nullable', 'integer'],
            'technical_metadata' => ['sometimes', 'nullable', 'array'],
            'captured_at' => ['sometimes', 'nullable', 'date'],
            'photographer_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'view_angle' => ['sometimes', 'nullable', 'string', 'max:80'],
            'caption' => ['sometimes', 'nullable', 'string'],
            'is_primary' => ['sometimes', 'nullable', 'boolean'],
            'is_public' => ['sometimes', 'nullable', 'boolean'],
            'access_level' => ['sometimes', 'nullable', 'in:public,member,internal,restricted'],
            'watermark_applied' => ['sometimes', 'nullable', 'boolean'],
            'sort_order' => ['sometimes', 'nullable', 'integer'],
        ];
    }
}