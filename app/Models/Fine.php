<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fine extends Model
{
    protected $fillable = [
        'fine_code',
        'borrowing_id',
        'member_user_id',
        'paid_amount',
        'waived_amount',
        'status',
        'paid_at',
        'processed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_amount' => 'decimal:2',
            'waived_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getSettlementAmountAttribute(): float
    {
        return (float) $this->paid_amount + (float) $this->waived_amount;
    }
}