<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class VocabularyTerm extends Model
{
    protected $fillable = [
        'authority_source',
        'term_code',
        'term_uri',
        'preferred_label',
        'alt_labels',
        'scope_note',
        'broader_id',
        'related_terms',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'alt_labels' => 'array',
            'related_terms' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function broader(): Relations\BelongsTo
    {
        return $this->belongsTo(VocabularyTerm::class, 'broader_id');
    }

    public function narrower(): Relations\HasMany
    {
        return $this->hasMany(VocabularyTerm::class, 'broader_id');
    }
}