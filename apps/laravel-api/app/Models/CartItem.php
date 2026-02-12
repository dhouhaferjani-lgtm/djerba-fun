<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'cart_id',
        'hold_id',
        'listing_id',
        'primary_contact',
        'guest_names',
        'extras',
        'listing_title',
        'slot_start',
        'slot_end',
        'quantity',
        'person_type_breakdown',
        'unit_price',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'primary_contact' => 'array',
            'guest_names' => 'array',
            'extras' => 'array',
            'listing_title' => 'array',
            'person_type_breakdown' => 'array',
            'slot_start' => 'datetime',
            'slot_end' => 'datetime',
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * Get the cart this item belongs to.
     */
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    /**
     * Get the booking hold for this item.
     */
    public function hold(): BelongsTo
    {
        return $this->belongsTo(BookingHold::class, 'hold_id');
    }

    /**
     * Get the listing for this item.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Calculate subtotal for this item.
     */
    public function getSubtotal(): float
    {
        // Use PriceCalculationService when listing is available for accurate per-type pricing
        if (! empty($this->person_type_breakdown) && $this->relationLoaded('listing') && $this->listing) {
            $priceService = app(\App\Services\PriceCalculationService::class);
            $result = $priceService->calculateTotal($this->listing, $this->person_type_breakdown, $this->currency);

            return $result['total'];
        }

        // Fallback: use cached unit_price (less accurate for multi-type bookings)
        if (! empty($this->person_type_breakdown)) {
            $total = 0;

            foreach ($this->person_type_breakdown as $type => $qty) {
                $total += $this->unit_price * $qty;
            }

            return $total;
        }

        // Simple calculation: unit price * quantity
        return (float) $this->unit_price * $this->quantity;
    }

    /**
     * Get per-person-type pricing map for the item's currency.
     * Returns e.g. ['adult' => 50.0, 'child' => 20.0] or null if listing not loaded.
     */
    public function getPersonTypePricing(): ?array
    {
        if (! $this->relationLoaded('listing') || ! $this->listing) {
            return null;
        }

        $pricing = $this->listing->pricing;
        $personTypes = $pricing['person_types'] ?? $pricing['personTypes'] ?? [];
        $priceKey = $this->currency === 'TND' ? 'tnd_price' : 'eur_price';

        $map = [];

        foreach ($personTypes as $pt) {
            $key = $pt['key'] ?? null;

            if ($key) {
                $map[$key] = (float) ($pt[$priceKey] ?? $pt['price'] ?? 0);
            }
        }

        return ! empty($map) ? $map : null;
    }

    /**
     * Calculate extras total for this item.
     */
    public function getExtrasTotal(): float
    {
        if (empty($this->extras)) {
            return 0;
        }

        $total = 0;

        foreach ($this->extras as $extra) {
            $total += ($extra['price'] ?? 0) * ($extra['quantity'] ?? 1);
        }

        return $total;
    }

    /**
     * Get total including extras.
     */
    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getExtrasTotal();
    }

    /**
     * Get localized title.
     */
    public function getTitle(string $locale = 'en'): string
    {
        if (is_array($this->listing_title)) {
            return $this->listing_title[$locale] ?? $this->listing_title['en'] ?? 'Activity';
        }

        return $this->listing_title ?? 'Activity';
    }

    /**
     * Check if the hold is still valid.
     */
    public function isHoldValid(): bool
    {
        return $this->hold && ! $this->hold->hasExpired() && $this->hold->isActive();
    }

    /**
     * Check if listing requires traveler names.
     */
    public function requiresTravelerNames(): bool
    {
        return $this->listing?->require_traveler_names ?? false;
    }

    /**
     * Set primary contact info.
     */
    public function setPrimaryContact(array $contact): void
    {
        $this->primary_contact = [
            'first_name' => $contact['first_name'] ?? $contact['firstName'] ?? '',
            'last_name' => $contact['last_name'] ?? $contact['lastName'] ?? '',
            'email' => $contact['email'] ?? '',
            'phone' => $contact['phone'] ?? '',
        ];
        $this->save();
    }

    /**
     * Set guest names (only if required by listing).
     */
    public function setGuestNames(array $names): void
    {
        $this->guest_names = $names;
        $this->save();
    }
}
