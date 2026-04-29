<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\HoldStatus;
use App\Enums\SlotStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvailabilitySlot extends Model
{
    use HasFactory;

    /**
     * The attributes that should be appended to model's array form.
     *
     * @var array<string>
     */
    protected $appends = ['remainingCapacity'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'listing_id',
        'availability_rule_id',
        'date',
        'start_time',
        'end_time',
        'capacity',
        'remaining_capacity',
        'base_price',
        'status',
        'currency',
        'price_overrides',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'status' => SlotStatus::class,
            'base_price' => 'decimal:2',
            'price_overrides' => 'array',
        ];
    }

    /**
     * Get the listing that owns the slot.
     */
    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    /**
     * Get the availability rule that generated this slot.
     */
    public function availabilityRule(): BelongsTo
    {
        return $this->belongsTo(AvailabilityRule::class);
    }

    /**
     * Get the booking holds for this slot.
     */
    public function holds(): HasMany
    {
        return $this->hasMany(BookingHold::class, 'slot_id');
    }

    /**
     * Get the confirmed bookings for this slot.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'availability_slot_id');
    }

    /**
     * Scope for available slots.
     */
    public function scopeAvailable($query)
    {
        return $query->whereIn('status', [SlotStatus::AVAILABLE, SlotStatus::LIMITED]);
    }

    /**
     * Scope for slots on a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->where('date', $date);
    }

    /**
     * Scope for slots between dates.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for future slots.
     */
    public function scopeFuture($query)
    {
        return $query->where('date', '>=', now()->toDateString());
    }

    /**
     * Scope to select only columns needed for API responses.
     * Prevents column mismatch issues by centralizing column selection.
     */
    public function scopeSelectApi($query)
    {
        return $query->select([
            'id', 'listing_id', 'availability_rule_id', 'date', 'start_time', 'end_time',
            'capacity', 'remaining_capacity', 'base_price', 'status', 'currency',
            'price_overrides',
            'created_at', 'updated_at',
        ]);
    }

    /**
     * Resolve the effective per-person-type prices for this slot in the given currency.
     *
     * Lenient per-key merge: any person-type listed in this slot's
     * price_overrides.person_types[] uses the slot's price; anything not
     * overridden falls back to the listing's pricing.person_types[].
     *
     * This is the single source of truth for "what does this slot cost
     * per person-type?" — every consumer (PriceCalculationService,
     * AvailabilitySlotResource) calls through here rather than rebuilding
     * the merge inline.
     *
     * @param  string  $currency  'TND' | 'EUR' (case-insensitive)
     * @param  array<int, array{key?: string, tnd_price?: int|float|string, eur_price?: int|float|string}>  $listingPersonTypes
     * @return array<string, float>  ['adult' => 120.0, 'child' => 30.0, ...]
     */
    public function getEffectivePersonTypePrices(string $currency, array $listingPersonTypes): array
    {
        $priceKey = strtoupper($currency) === 'TND' ? 'tnd_price' : 'eur_price';

        $overridesByKey = [];
        $overrides = $this->price_overrides;
        if (is_array($overrides) && isset($overrides['person_types']) && is_array($overrides['person_types'])) {
            foreach ($overrides['person_types'] as $entry) {
                if (! is_array($entry) || ! isset($entry['key'])) {
                    continue;
                }
                if (array_key_exists($priceKey, $entry) && $entry[$priceKey] !== null && $entry[$priceKey] !== '') {
                    $overridesByKey[(string) $entry['key']] = (float) $entry[$priceKey];
                }
            }
        }

        $effective = [];
        foreach ($listingPersonTypes as $listingPt) {
            if (! is_array($listingPt) || ! isset($listingPt['key'])) {
                continue;
            }
            $key = (string) $listingPt['key'];
            if (array_key_exists($key, $overridesByKey)) {
                $effective[$key] = $overridesByKey[$key];
            } else {
                $effective[$key] = (float) ($listingPt[$priceKey] ?? 0);
            }
        }

        return $effective;
    }

    /**
     * Update the slot status based on remaining capacity.
     * Uses the computed remainingCapacity accessor.
     */
    public function updateStatus(): void
    {
        $remaining = $this->remainingCapacity; // Use computed accessor

        $this->status = match (true) {
            $remaining === 0 => SlotStatus::SOLD_OUT,
            $remaining <= $this->capacity * 0.3 => SlotStatus::LIMITED,
            default => SlotStatus::AVAILABLE,
        };

        $this->save();
    }

    /**
     * Check if slot is bookable.
     * Uses computed remainingCapacity for real-time availability.
     * Only respects BLOCKED status (vendor decision), ignores stale SOLD_OUT status.
     */
    public function isBookable(): bool
    {
        // Respect manually BLOCKED dates (vendor decision)
        if ($this->status === SlotStatus::BLOCKED) {
            return false;
        }

        // For all other statuses (AVAILABLE/LIMITED/SOLD_OUT), rely ONLY on
        // computed remaining capacity. The status column can become stale
        // when holds expire without updating the status.
        return $this->remainingCapacity > 0;
    }

    /**
     * Computed accessor for remaining capacity.
     * Dynamically calculates based on confirmed bookings and active holds.
     */
    public function getRemainingCapacityAttribute(): int
    {
        // Count spots taken by confirmed/completed bookings
        $bookedQuantity = $this->bookings()
            ->whereIn('status', [
                BookingStatus::CONFIRMED,
                BookingStatus::COMPLETED,
            ])
            ->sum('quantity');

        // Count spots held by active holds (temporary reservations)
        $heldQuantity = $this->holds()
            ->where('status', HoldStatus::ACTIVE)
            ->where('expires_at', '>', now())
            ->sum('quantity');

        // Calculate remaining: capacity - (confirmed bookings + active holds)
        return max(0, $this->capacity - $bookedQuantity - $heldQuantity);
    }
}
