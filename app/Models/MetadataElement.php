<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetadataElement extends Model
{
    protected $fillable = [
        'standard',
        'element_key',
        'label',
        'data_type',
        'is_repeatable',
        'is_required',
        'applies_to',
        'vocabulary_source',
        'sort_order',
        'help_text',
    ];

    protected function casts(): array
    {
        return [
            'is_repeatable' => 'boolean',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function itemMetadata(): HasMany
    {
        return $this->hasMany(ItemMetadata::class);
    }
}