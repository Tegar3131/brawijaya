<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\SoftDeletes;

class Collection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ulid',
        'record_code',
        'unit_type',
        'collection_type',
        'title',
        'subtitle',
        'description',
        'language_code',
        'rights_status',
        'date_display',
        'year_start',
        'year_end',
        'category_id',
        'current_location_id',
        'publication_status',
        'visibility',
        'is_featured',
        'featured_order',
        'created_by',
        'updated_by',
        'deleted_by',
        'archived_reason',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'year_start' => 'integer',
            'year_end' => 'integer',
            'is_featured' => 'boolean',
            'archived_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function libraryItem(): Relations\HasOne
    {
        return $this->hasOne(LibraryItem::class);
    }

    public function libraryCopies(): Relations\HasMany
    {
        return $this->hasMany(LibraryCopy::class);
    }

    public function museumItem(): Relations\HasOne
    {
        return $this->hasOne(MuseumItem::class);
    }

    public function category(): Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currentLocation(): Relations\BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    public function creatorUser(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creators(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Creator::class, 'collection_creator')
            ->withPivot(['role', 'sort_order', 'is_primary', 'notes'])
            ->withTimestamps();
    }

    public function subjects(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'collection_subject')
            ->withPivot(['subject_type', 'sort_order'])
            ->withTimestamps();
    }

    public function digitalAssets(): Relations\HasMany
    {
        return $this->hasMany(DigitalAsset::class);
    }

    public function metadata(): Relations\HasMany
{
    return $this->hasMany(ItemMetadata::class);
}

public function versions(): Relations\HasMany
{
    return $this->hasMany(CollectionVersion::class);
}

public function locationHistories(): Relations\HasMany
{
    return $this->hasMany(LocationHistory::class);
}

public function reservations(): Relations\HasMany
{
    return $this->hasMany(Reservation::class);
}

    public function primaryAsset(): Relations\HasOne
    {
        return $this->hasOne(DigitalAsset::class)
            ->where('is_primary', true)
            ->orderBy('sort_order');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('publication_status', 'published')
            ->whereNull('deleted_at');
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->published()
            ->where('visibility', 'public');
    }

    public function scopeLibrary(Builder $query): Builder
    {
        return $query->where('unit_type', 'library');
    }

    public function scopeMuseum(Builder $query): Builder
    {
        return $query->where('unit_type', 'museum');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)
            ->orderBy('featured_order');
    }

    public function getDisplayTitleAttribute(): string
    {
        return trim($this->subtitle ? "{$this->title}: {$this->subtitle}" : $this->title);
    }

    public function isLibrary(): bool
    {
        return $this->unit_type === 'library';
    }

    public function isMuseum(): bool
    {
        return $this->unit_type === 'museum';
    }
}