<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuseumItemRelation extends Model
{
    protected $fillable = [
        'source_museum_item_id',
        'target_museum_item_id',
        'relation_type',
        'inverse_relation_type',
        'notes',
        'created_by',
    ];

    public function sourceItem(): BelongsTo
    {
        return $this->belongsTo(MuseumItem::class, 'source_museum_item_id');
    }

    public function targetItem(): BelongsTo
    {
        return $this->belongsTo(MuseumItem::class, 'target_museum_item_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}