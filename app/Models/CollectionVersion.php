<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'collection_id',
        'version_no',
        'changed_by',
        'change_reason',
        'snapshot_json',
        'diff_json',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'version_no' => 'integer',
            'snapshot_json' => 'array',
            'diff_json' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}