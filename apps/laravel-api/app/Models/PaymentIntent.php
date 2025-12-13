<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentIntent extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_id',
        'amount',
        'currency',
        'payment_method',
        'status',
        'gateway',
        'gateway_id',
        'metadata',
        'paid_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the booking for this payment intent.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Check if payment is successful.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCEEDED;
    }

    /**
     * Check if payment has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [
            PaymentStatus::PENDING,
            PaymentStatus::PROCESSING,
        ], true);
    }

    /**
     * Mark payment as successful.
     */
    public function markAsSuccessful(): void
    {
        $this->update([
            'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => PaymentStatus::FAILED,
            'failed_at' => now(),
        ]);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, PaymentStatus $status)
    {
        return $query->where('status', $status);
    }
}
