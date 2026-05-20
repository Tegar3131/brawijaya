<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'phone',
        'address',
        'user_type',
        'unit',
        'status',
        'member_number',
        'identity_type',
        'identity_number',
        'member_category',
        'membership_status',
        'member_active_until',
        'identity_document_path',
        'member_verified_at',
        'member_verified_by',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_enabled_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'member_active_until' => 'date',
            'member_verified_at' => 'datetime',
            'two_factor_enabled_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdCollections(): HasMany
    {
        return $this->hasMany(Collection::class, 'created_by');
    }

    public function borrowings(): HasMany
    {
        return $this->hasMany(Borrowing::class, 'member_user_id');
    }

    public function activeBorrowings(): HasMany
    {
        return $this->borrowings()->whereIn('status', ['borrowed', 'overdue']);
    }

    public function uploadedAssets(): HasMany
    {
        return $this->hasMany(DigitalAsset::class, 'uploaded_by');
    }

    public function bookmarks(): BelongsToMany
    {
        return $this->belongsToMany(Collection::class, 'user_bookmarks')
            ->withPivot(['folder_name', 'notes'])
            ->withTimestamps();
    }

    public function hasActiveMembership(): bool
    {
        return $this->user_type === 'member'
            && $this->status === 'active'
            && $this->membership_status === 'active'
            && (
                $this->member_active_until === null
                || $this->member_active_until->isFuture()
                || $this->member_active_until->isToday()
            );
    }

    public function canBorrow(): bool
    {
        if (! $this->hasActiveMembership()) {
            return false;
        }

        $hasUnpaidFine = $this->borrowings()
            ->whereColumn('fine_amount', '>', 'fine_paid_amount')
            ->exists();

        if ($hasUnpaidFine) {
            return false;
        }

        $maxBorrowItems = (int) ($this->roles()->max('max_borrow_items') ?? 0);

        if ($maxBorrowItems <= 0) {
            return false;
        }

        return $this->activeBorrowings()->count() < $maxBorrowItems;
    }

    public function defaultBorrowDurationDays(): int
    {
        return (int) ($this->roles()->max('borrow_duration_days') ?? 0);
    }

    public function scopeActiveMembers(Builder $query): Builder
    {
        return $query->where('user_type', 'member')
            ->where('status', 'active')
            ->where('membership_status', 'active');
    }
}