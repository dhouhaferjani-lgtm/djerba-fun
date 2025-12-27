<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CartPayment extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'cart_id',
        'amount',
        'currency',
        'status',
        'payment_method',
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
            'status' => PaymentStatus::class,
            'metadata' => 'array',
            'paid_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /**
     * Get the cart this payment is for.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get all bookings created from this payment.
     */
    public function bookings(): BelongsToMany
    {
        return $this->belongsToMany(Booking::class, 'cart_payment_bookings')
            ->withPivot('amount')
            ->withTimestamps();
    }

    /**
     * Check if payment succeeded.
     */
    public function isSuccessful(): bool
    {
        return $this->status === PaymentStatus::SUCCEEDED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment failed.
     */
    public function isFailed(): bool
    {
        return $this->status === PaymentStatus::FAILED;
    }

    /**
     * Mark payment as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => PaymentStatus::PROCESSING]);
    }

    /**
     * Mark payment as succeeded.
     */
    public function markAsSucceeded(string $gatewayId): void
    {
        $this->update([
            'status' => PaymentStatus::SUCCEEDED,
            'gateway_id' => $gatewayId,
            'paid_at' => now(),
        ]);
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(?string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        $metadata['failure_reason'] = $reason;

        $this->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => $metadata,
            'failed_at' => now(),
        ]);
    }
}
