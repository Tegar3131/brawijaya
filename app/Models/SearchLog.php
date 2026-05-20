<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'query',
        'filters',
        'results_count',
        'collection_type',
        'ip_address',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'results_count' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getDecodedFiltersAttribute(): ?array
    {
        if (! $this->filters) {
            return null;
        }

        $decoded = json_decode($this->filters, true);

        return is_array($decoded) ? $decoded : null;
    }
}