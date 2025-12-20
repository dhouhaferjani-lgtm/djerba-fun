<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataDeletionRequest extends Model
{
    use HasFactory, HasUuids;

    /**
     * Possible statuses.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'email',
        'status',
        'reason',
        'requested_at',
        'processed_at',
        'processed_by',
        'admin_notes',
        'data_deleted',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'processed_at' => 'datetime',
            'data_deleted' => 'array',
        ];
    }

    /**
     * Get the user who requested deletion.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who processed this request.
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Alias for processor relationship (used by Filament).
     */
    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Scope to get pending requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get requests by email.
     */
    public function scopeForEmail($query, string $email)
    {
        return $query->where('email', $email);
    }

    /**
     * Check if this request is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if this request has been processed.
     */
    public function isProcessed(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_REJECTED]);
    }

    /**
     * Mark this request as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    /**
     * Mark this request as completed.
     */
    public function markAsCompleted(User $processedBy, array $dataDeleted = [], ?string $notes = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'processed_at' => now(),
            'processed_by' => $processedBy->id,
            'data_deleted' => $dataDeleted,
            'admin_notes' => $notes,
        ]);
    }

    /**
     * Mark this request as rejected.
     */
    public function markAsRejected(User $processedBy, string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'processed_at' => now(),
            'processed_by' => $processedBy->id,
            'admin_notes' => $reason,
        ]);
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }
}
