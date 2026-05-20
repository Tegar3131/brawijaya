<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalAsset extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'collection_id',
        'uploaded_by',
        'asset_type',
        'file_role',
        'disk',
        'path',
        'public_url',
        'thumbnail_path',
        'watermarked_path',
        'filename',
        'original_filename',
        'mime_type',
        'extension',
        'size_bytes',
        'checksum_sha256',
        'width_px',
        'height_px',
        'duration_seconds',
        'technical_metadata',
        'captured_at',
        'photographer_name',
        'view_angle',
        'caption',
        'is_primary',
        'is_public',
        'access_level',
        'watermark_applied',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'width_px' => 'integer',
            'height_px' => 'integer',
            'duration_seconds' => 'integer',
            'technical_metadata' => 'array',
            'captured_at' => 'date',
            'is_primary' => 'boolean',
            'is_public' => 'boolean',
            'watermark_applied' => 'boolean',
            'sort_order' => 'integer',
            'deleted_at' => 'datetime',
        ];
    }

    public function collection(): Relations\BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function uploader(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}