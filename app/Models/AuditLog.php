<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'auditable_type',
        'auditable_id',
        'module',
        'action',
        'event',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::updating(function (): void {
            throw new RuntimeException('AuditLog is immutable');
        });

        static::deleting(function (): void {
            throw new RuntimeException('AuditLog is immutable');
        });
    }
}