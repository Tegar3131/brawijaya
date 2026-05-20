<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'group',
        'value_json',
        'description',
        'is_public',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'value_json' => 'array',
            'is_public' => 'boolean',
        ];
    }

    public function updater(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}