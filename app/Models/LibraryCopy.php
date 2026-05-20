<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\SoftDeletes;

class LibraryCopy extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'collection_id',
        'copy_number',
        'barcode',
        'call_number',
        'location_id',
        'condition_grade',
        'status',
        'acquired_at',
        'last_inventory_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'acquired_at' => 'date',
            'last_inventory_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function collection(): Relations\BelongsTo
    {
        return $this->belongsTo(Collection::class);
    }

    public function location(): Relations\BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function borrowings(): Relations\HasMany
    {
        return $this->hasMany(Borrowing::class);
    }

    public function activeBorrowing(): Relations\HasOne
    {
        return $this->hasOne(Borrowing::class)
            ->whereIn('status', ['borrowed', 'overdue']);
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }
}