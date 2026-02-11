<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'booking_number',
        'user_id',
        'session_id',
        'magic_token',
        'magic_token_expires_at',
        'listing_id',
        'availability_slot_id',
        'coupon_id',
        'cart_payment_id',
        'partner_id',
        'quantity',
        'person_type_breakdown',
        'total_amount',
        'discount_amount',
        'currency',
        'billing_country_code',
        'billing_city',
        'billing_postal_code',
        'billing_address_line1',
        'billing_address_line2',
        'pricing_snapshot',
        'pricing_disclosed',
        'status',
        'traveler_info',
        'travelers',
        'extras',
        'partner_metadata',
        'billing_contact',
        'confirmed_at',
        'cancelled_at',
        'cancellation_reason',
        'traveler_details_status',
        'traveler_details_completed_at',
        'linked_at',
        'linked_method',
        'payment_notes',
        'manual_payment_confirmed_by',
        'manual_payment_confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'traveler_info' => 'array',
            'travelers' => 'array',
            'extras' => 'array',
            'partner_metadata' => 'array',
            'billing_contact' => 'array',
            'person_type_breakdown' => 'array',
            'pricing_snapshot' => 'array',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'pricing_disclosed' => 'boolean',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'magic_token_expires_at' => 'datetime',
            'traveler_details_status' => 'string',
            'traveler_details_completed_at' => 'datetime',
            'linked_at' => 'datetime',
            'linked_method' => 'string',
            'manual_payment_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Check if the magic token is valid (exists and not expired).
     */
    public function hasMagicTokenValid(): bool
    {
        return $this->magic_token !== null
            && $this->magic_token_expires_at !== null
            && $this->magic_token_expires_at->isFuture();
    }

    /**
     * Generate a new magic token with expiration.
     */
    public function generateMagicToken(int $expirationDays = 30): void
    {
        $this->magic_token = \Illuminate\Support\Str::random(64);
        $this->magic_token_expires_at = now()->addDays($expirationDays);
        $this->save();
    }

    /**
     * Get the magic link URL for this booking.
     */
    public function getMagicLinkUrl(): ?string
    {
        if (! $this->magic_token) {
            return null;
        }

        $frontendUrl = config('app.frontend_url', 'https://www.go-adventure.net');

        return "{$frontendUrl}/booking/{$this->magic_token}";
    }

    /**
     * Get all travelers for this booking.
     * Falls back to traveler_info wrapped in array if travelers not set.
     */
    public function getAllTravelers(): array
    {
        if (! empty($this->travelers)) {
            return $this->travelers;
        }

        // Fallback for backward compatibility
        if (! empty($this->traveler_info)) {
            return [$this->traveler_info];
        }

        return [];
    }

    /**
     * Get the primary traveler (first in list or traveler_info).
     */
    public function getPrimaryTraveler(): ?array
    {
        $travelers = $this->getAllTravelers();

        return $travelers[0] ?? null;
    }

    /**
     * Get the primary traveler's email.
     */
    public function getPrimaryEmail(): ?string
    {
        $primary = $this->getPrimaryTraveler();

        return $primary['email'] ?? null;
    }

    /**
     * Get the user who made the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the partner who created the booking (if created via Partner API).
     */
    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
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
     * Get the coupon applied to this booking.
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * Get the cart payment for this booking.
     */
    public function cartPayment(): BelongsTo
    {
        return $this->belongsTo(CartPayment::class);
    }

    /**
     * Get the review for this booking.
     */
    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    /**
     * Get all participants for this booking.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(BookingParticipant::class);
    }

    /**
     * Get the user who manually confirmed payment.
     */
    public function manualPaymentConfirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manual_payment_confirmed_by');
    }

    /**
     * Get all booking extras for this booking.
     */
    public function bookingExtras(): HasMany
    {
        return $this->hasMany(BookingExtra::class);
    }

    /**
     * Get active booking extras.
     */
    public function activeBookingExtras(): HasMany
    {
        return $this->bookingExtras()->active();
    }

    /**
     * Get total extras amount for this booking.
     */
    public function getExtrasTotal(?string $currency = null): float
    {
        $currency = $currency ?? $this->currency;
        $column = $currency === 'TND' ? 'subtotal_tnd' : 'subtotal_eur';

        return (float) $this->activeBookingExtras()->sum($column);
    }

    /**
     * Check if booking has a review.
     */
    public function hasReview(): bool
    {
        return $this->review()->exists();
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
     * Check if all participant details have been filled.
     */
    public function participantsComplete(): bool
    {
        if ($this->participants()->count() === 0) {
            return false;
        }

        return $this->participants()->incomplete()->count() === 0;
    }

    /**
     * Check if vouchers can be generated.
     * Vouchers require: confirmed booking + participant names (if listing requires them).
     */
    public function canGenerateVouchers(): bool
    {
        if (! $this->isConfirmed()) {
            return false;
        }

        // If listing requires participant names, check they're complete
        if ($this->listing?->require_traveler_names ?? false) {
            return $this->participantsComplete();
        }

        // If names not required, vouchers can be generated immediately
        return true;
    }

    /**
     * Get billing contact's full name.
     */
    public function getBillingContactNameAttribute(): ?string
    {
        if (! $this->billing_contact) {
            return null;
        }

        $first = $this->billing_contact['first_name'] ?? '';
        $last = $this->billing_contact['last_name'] ?? '';

        return trim("{$first} {$last}") ?: null;
    }

    /**
     * Get billing contact's email.
     */
    public function getBillingContactEmailAttribute(): ?string
    {
        return $this->billing_contact['email'] ?? null;
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

    /**
     * Scope to select only columns needed for API responses.
     * Prevents column mismatch issues by centralizing column selection.
     */
    public function scopeSelectApi($query)
    {
        return $query->select([
            'id', 'booking_number', 'user_id', 'listing_id', 'availability_slot_id',
            'quantity', 'total_amount', 'discount_amount', 'currency', 'status',
            'traveler_info', 'travelers', 'extras', 'billing_contact',
            'confirmed_at', 'cancelled_at', 'cancellation_reason',
            'created_at', 'updated_at', 'session_id', 'partner_id'
        ]);
    }

    /**
     * Check if this booking requires traveler details.
     */
    public function requiresTravelerDetails(): bool
    {
        return $this->traveler_details_status !== 'not_required';
    }

    /**
     * Check if traveler details are complete.
     */
    public function travelerDetailsComplete(): bool
    {
        return $this->traveler_details_status === 'complete';
    }

    /**
     * Check if traveler details are pending or partial.
     */
    public function travelerDetailsPending(): bool
    {
        return in_array($this->traveler_details_status, ['pending', 'partial'], true);
    }

    /**
     * Update traveler details status based on participant completion.
     */
    public function updateTravelerDetailsStatus(): void
    {
        if (! $this->listing?->requiresTravelerNames()) {
            $this->update(['traveler_details_status' => 'not_required']);

            return;
        }

        $totalParticipants = $this->participants()->count();
        $completeParticipants = $this->participants()
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->count();

        if ($completeParticipants === 0) {
            $status = 'pending';
        } elseif ($completeParticipants < $totalParticipants) {
            $status = 'partial';
        } else {
            $status = 'complete';
        }

        $this->update([
            'traveler_details_status' => $status,
            'traveler_details_completed_at' => $status === 'complete' ? now() : null,
        ]);
    }

    /**
     * Check if this booking requires pricing disclosure.
     * Returns true if the price was changed due to PPP adjustments.
     */
    public function requiresPricingDisclosure(): bool
    {
        if (!$this->pricing_snapshot) {
            return false;
        }

        return $this->pricing_snapshot['price_changed'] === true;
    }

    /**
     * Get the billing address as an array.
     *
     * @return array{country_code: string|null, city: string|null, postal_code: string|null, address_line1: string|null, address_line2: string|null}
     */
    public function getBillingAddress(): array
    {
        return [
            'country_code' => $this->billing_country_code,
            'city' => $this->billing_city,
            'postal_code' => $this->billing_postal_code,
            'address_line1' => $this->billing_address_line1,
            'address_line2' => $this->billing_address_line2,
        ];
    }
}
