<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowHistory extends Model
{
    protected $table = 'borrow_history';

    public $timestamps = false;

    protected $fillable = [
        'borrowing_id',
        'library_copy_id',
        'member_user_id',
        'event_type',
        'event_at',
        'old_status',
        'new_status',
        'old_due_date',
        'new_due_date',
        'amount',
        'actor_id',
        'notes',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'event_at' => 'datetime',
            'old_due_date' => 'date',
            'new_due_date' => 'date',
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    public function libraryCopy(): BelongsTo
    {
        return $this->belongsTo(LibraryCopy::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}