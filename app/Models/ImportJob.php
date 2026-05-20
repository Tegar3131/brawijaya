<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    protected $fillable = [
        'ulid',
        'type',
        'source_file_path',
        'original_filename',
        'status',
        'total_rows',
        'valid_rows',
        'error_rows',
        'duplicate_rows',
        'summary_json',
        'error_report_path',
        'started_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'valid_rows' => 'integer',
            'error_rows' => 'integer',
            'duplicate_rows' => 'integer',
            'summary_json' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function starter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function markProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markCompleted(array $summary = []): void
    {
        $this->update([
            'status' => 'completed',
            'summary_json' => $summary,
            'finished_at' => now(),
        ]);
    }

    public function markFailed(array $summary = []): void
    {
        $this->update([
            'status' => 'failed',
            'summary_json' => $summary,
            'finished_at' => now(),
        ]);
    }
}