<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowRenewal extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'borrowing_id',
        'renewed_by',
        'old_due_date',
        'new_due_date',
        'renewal_method',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_due_date' => 'date',
            'new_due_date' => 'date',
            'created_at' => 'datetime',
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function renewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renewed_by');
    }
}