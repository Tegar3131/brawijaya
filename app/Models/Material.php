<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Material extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'authority_uri',
        'type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function museumItems(): Relations\BelongsToMany
    {
        return $this->belongsToMany(MuseumItem::class, 'museum_item_materials')
            ->withPivot('is_primary');
    }
}