<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class MuseumItem extends Model
{
    protected $fillable = [
        'collection_id',
        'inventory_number',
        'object_name',
        'object_type_label',
        'object_type_uri',
        'classification',
        'maker_name',
        'maker_uri',
        'culture',
        'period_display',
        'made_year_start',
        'made_year_end',
        'material_summary',
        'technique_summary',
        'height_cm',
        'width_cm',
        'length_depth_cm',
        'weight_gram',
        'condition_current',
        'condition_checked_at',
        'condition_notes',
        'provenance_history',
        'acquisition_method',
        'acquisition_source',
        'acquisition_date',
        'is_sensitive',
    ];

    protected $casts = [
        'made_year_start' => 'integer',
        'made_year_end' => 'integer',
        'height_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'length_depth_cm' => 'decimal:2',
        'weight_gram' => 'decimal:2',
        'condition_checked_at' => 'date',
        'acquisition_date' => 'date',
        'is_sensitive' => 'boolean',
    ];

    public function collection(): Relations\BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function conditionReports(): Relations\HasMany
    {
        return $this->hasMany(ConditionReport::class);
    }

    public function latestConditionReport(): Relations\HasOne
    {
        return $this->hasOne(ConditionReport::class)->latestOfMany('inspected_at');
    }

    public function materials(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Material::class, 'museum_item_materials')
            ->withPivot('is_primary');
    }

    public function exhibitions(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Exhibition::class, 'exhibition_items')
            ->withPivot(['status', 'start_at', 'end_at', 'notes'])
            ->withTimestamps();
    }

    public function outgoingRelations(): Relations\HasMany
    {
        return $this->hasMany(MuseumItemRelation::class, 'source_museum_item_id');
    }

    public function incomingRelations(): Relations\HasMany
    {
        return $this->hasMany(MuseumItemRelation::class, 'target_museum_item_id');
    }

    public function needsConservation(): bool
    {
        return in_array($this->condition_current, ['poor', 'critical'], true);
    }
}