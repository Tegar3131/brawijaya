<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Location extends Model
{
    protected $fillable = [
        'parent_id',
        'code',
        'name',
        'location_type',
        'description',
        'is_public',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
        ];
    }

    public function parent(): Relations\BelongsTo
    {
        return $this->belongsTo(Location::class, 'parent_id');
    }

    public function children(): Relations\HasMany
    {
        return $this->hasMany(Location::class, 'parent_id');
    }

    public function collections(): Relations\HasMany
    {
        return $this->hasMany(Collection::class, 'current_location_id');
    }
}