<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Booking extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'booking_number',
        'user_id',
        'listing_id',
        'availability_slot_id',
        'quantity',
        'total_amount',
        'currency',
        'status',
        'traveler_info',
        'extras',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'traveler_info' => 'array',
            'extras' => 'array',
            'total_amount' => 'decimal:2',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Get the user who made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the listing being booked.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the availability slot for this booking.
     */
    public function availabilitySlot(): BelongsTo
    {
        return $this->belongsTo(AvailabilitySlot::class);
    }

    /**
     * Get all payment intents for this booking.
     */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }

    /**
     * Get the latest payment intent.
     */
    public function latestPaymentIntent(): ?PaymentIntent
    {
        return $this->paymentIntents()->latest()->first();
    }

    /**
     * Check if booking can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            BookingStatus::PENDING_PAYMENT,
            BookingStatus::CONFIRMED,
        ], true);
    }

    /**
     * Check if booking is confirmed.
     */
    public function isConfirmed(): bool
    {
        return $this->status === BookingStatus::CONFIRMED;
    }

    /**
     * Check if booking is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === BookingStatus::CANCELLED;
    }

    /**
     * Scope to filter by status.
     */
    public function scopeWithStatus($query, BookingStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
