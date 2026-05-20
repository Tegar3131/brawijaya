<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class Subject extends Model
{
    protected $fillable = [
        'term',
        'slug',
        'vocabulary_source',
        'authority_uri',
        'broader_id',
        'type',
        'scope_note',
    ];

    public function broader(): Relations\BelongsTo
    {
        return $this->belongsTo(Subject::class, 'broader_id');
    }

    public function narrower(): Relations\HasMany
    {
        return $this->hasMany(Subject::class, 'broader_id');
    }

    public function collections(): Relations\BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'collection_subject')
            ->withPivot(['subject_type', 'sort_order'])
            ->withTimestamps();
    }
}