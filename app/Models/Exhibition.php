<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Exhibition extends Model
{
    protected $fillable = [
        'ulid',
        'name',
        'slug',
        'theme',
        'start_date',
        'end_date',
        'location_id',
        'description',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function location(): Relations\BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function creator(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function museumItems(): Relations\BelongsToMany
    {
        return $this->belongsToMany(MuseumItem::class, 'exhibition_items')
            ->withPivot(['status', 'start_at', 'end_at', 'notes'])
            ->withTimestamps();
    }
}