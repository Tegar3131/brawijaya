<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;


class Borrowing extends Model
{
    protected $fillable = [
        'ulid',
        'transaction_code',
        'member_user_id',
        'library_copy_id',
        'borrowed_at',
        'due_date',
        'returned_at',
        'status',
        'renewal_count',
        'fine_amount',
        'fine_paid_amount',
        'fine_paid_at',
        'processed_by',
        'returned_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'borrowed_at' => 'datetime',
            'due_date' => 'date',
            'returned_at' => 'datetime',
            'renewal_count' => 'integer',
            'fine_amount' => 'decimal:2',
            'fine_paid_amount' => 'decimal:2',
            'fine_paid_at' => 'datetime',
        ];
    }

    public function member(): Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'member_user_id');
    }

    public function libraryCopy(): Relations\BelongsTo
    {
        return $this->belongsTo(LibraryCopy::class);
    }
    public function renewals(): Relations\HasMany
    {
        return $this->hasMany(BorrowRenewal::class);
    }

    public function history(): Relations\HasMany
    {
        return $this->hasMany(BorrowHistory::class);
    }

    public function finePayments(): Relations\HasMany
    {
        return $this->hasMany(Fine::class);
    }

    public function syncFinePaymentStatus(): void
    {
        $paid = (float) $this->finePayments()->sum('paid_amount');
        $waived = (float) $this->finePayments()->sum('waived_amount');
        $settled = $paid + $waived;

        $this->fine_paid_amount = $settled;

        if ((float) $this->fine_amount > 0 && $settled >= (float) $this->fine_amount) {
            $this->fine_paid_at = now();
        }

        $this->save();
    }
    public function calculateFine(?CarbonInterface $returnDate = null): float
    {
        $returnDate = $returnDate ?? now();

        if ($returnDate->toDateString() <= $this->due_date->toDateString()) {
            return 0.00;
        }

        $daysLate = $this->due_date->diffInDays($returnDate);

        $setting = SystemSetting::where('key', 'circulation.default_fine_per_day')->first();
        $finePerDay = (float) data_get($setting?->value_json, 'amount', 0);

        return round($daysLate * $finePerDay, 2);
    }

    public function hasUnpaidFine(): bool
    {
        return (float) $this->fine_amount > (float) $this->fine_paid_amount;
    }
}