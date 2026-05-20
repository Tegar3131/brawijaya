<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionReport extends Model
{
    protected $fillable = [
        'museum_item_id',
        'condition_grade',
        'inspected_by',
        'inspected_at',
        'description',
        'recommendation',
        'priority',
        'next_review_at',
        'asset_id',
    ];

    protected function casts(): array
    {
        return [
            'inspected_at' => 'datetime',
            'next_review_at' => 'date',
        ];
    }

    public function museumItem(): BelongsTo
    {
        return $this->belongsTo(MuseumItem::class);
    }

    public function inspector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inspected_by');
    }

    public function asset(): BelongsTo
    {
        return $this->belongsTo(DigitalAsset::class, 'asset_id');
    }
}