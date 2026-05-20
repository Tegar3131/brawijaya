<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'type',
        'description',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function parent(): Relations\BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): Relations\HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function collections(): Relations\HasMany
    {
        return $this->hasMany(Collection::class);
    }
}