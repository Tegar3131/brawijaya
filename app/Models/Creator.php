<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Creator extends Model
{
    protected $fillable = [
        'name',
        'normalized_name',
        'authority_source',
        'authority_uri',
        'birth_death_dates',
        'biography',
    ];

    public function collections(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_creator')
            ->withPivot(['role', 'sort_order', 'is_primary', 'notes'])
            ->withTimestamps();
    }
}