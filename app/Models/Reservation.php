<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'ulid',
        'code',
        'member_user_id',
        'collection_id',
        'library_copy_id',
        'queue_position',
        'status',
        'reserved_at',
        'expires_at',
        'notified_at',
        'fulfilled_at',
    ];

    protected function casts(): array
    {
        return [
            'queue_position' => 'integer',
            'reserved_at' => 'datetime',
            'expires_at' => 'datetime',
            'notified_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function libraryCopy(): BelongsTo
    {
        return $this->belongsTo(LibraryCopy::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}