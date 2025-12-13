<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PayoutMethod;
use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payout extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'vendor_id',
        'amount',
        'currency',
        'status',
        'payout_method',
        'bank_details',
        'reference',
        'processed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'status' => PayoutStatus::class,
            'payout_method' => PayoutMethod::class,
            'bank_details' => 'encrypted:array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * Get the vendor for this payout.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    /**
     * Mark payout as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => PayoutStatus::PROCESSING,
        ]);
    }

    /**
     * Mark payout as completed.
     */
    public function markAsCompleted(string $reference): void
    {
        $this->update([
            'status' => PayoutStatus::COMPLETED,
            'reference' => $reference,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark payout as failed.
     */
    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => PayoutStatus::FAILED,
            'notes' => $reason,
        ]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, PayoutStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by vendor.
     */
    public function scopeForVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope to filter pending payouts.
     */
    public function scopePending($query)
    {
        return $query->where('status', PayoutStatus::PENDING);
    }
}
