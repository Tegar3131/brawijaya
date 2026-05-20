<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class LibraryItem extends Model
{
    protected $fillable = [
        'collection_id',
        'bibliographic_level',
        'isbn13',
        'isbn10',
        'issn',
        'doi',
        'publisher_name',
        'publisher_place',
        'edition',
        'publication_year',
        'publication_date',
        'ddc_classification',
        'call_number',
        'marc_leader',
        'marc_control_number',
        'marc_raw_json',
        'mods_xml',
        'physical_extent',
        'physical_dimensions',
        'pages',
        'illustrations',
        'series_title',
        'source_acquisition',
        'acquired_at',
    ];

    protected function casts(): array
    {
        return [
            'publication_year' => 'integer',
            'publication_date' => 'date',
            'marc_raw_json' => 'array',
            'pages' => 'integer',
            'acquired_at' => 'date',
        ];
    }

    public function collection(): Relations\BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function copies(): Relations\HasMany
    {
        return $this->hasMany(LibraryCopy::class, 'collection_id', 'collection_id');
    }

    public function availableCopies(): Relations\HasMany
    {
        return $this->copies()->where('status', 'available');
    }

    public function getAvailabilityStatusAttribute(): string
    {
        if ($this->availableCopies()->exists()) {
            return 'available';
        }

        if ($this->copies()->where('status', 'borrowed')->exists()) {
            return 'borrowed';
        }

        if ($this->copies()->where('status', 'reserved')->exists()) {
            return 'reserved';
        }

        return 'unavailable';
    }
}