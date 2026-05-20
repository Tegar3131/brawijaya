<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemMetadata extends Model
{
    protected $table = 'item_metadata';

    protected $fillable = [
        'collection_id',
        'metadata_element_id',
        'is_repeatable_field',
        'value_string',
        'value_text',
        'value_integer',
        'value_decimal',
        'value_date',
        'value_datetime',
        'value_json',
        'language_code',
        'authority_uri',
        'sort_order',
        'source',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_repeatable_field' => 'boolean',
            'value_integer' => 'integer',
            'value_decimal' => 'decimal:4',
            'value_date' => 'date',
            'value_datetime' => 'datetime',
            'value_json' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function metadataElement(): BelongsTo
    {
        return $this->belongsTo(MetadataElement::class);
    }

    public function creatorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayValueAttribute(): mixed
    {
        return $this->value_string
            ?? $this->value_text
            ?? $this->value_integer
            ?? $this->value_decimal
            ?? $this->value_date
            ?? $this->value_datetime
            ?? $this->value_json;
    }
}