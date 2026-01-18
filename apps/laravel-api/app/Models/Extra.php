<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraCategory;
use App\Enums\ExtraPricingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Translatable\HasTranslations;

class Extra extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'name',
        'description',
        'short_description',
        'image_url',
        'thumbnail_url',
        'pricing_type',
        'base_price_tnd',
        'base_price_eur',
        'person_type_prices',
        'min_quantity',
        'max_quantity',
        'default_quantity',
        'track_inventory',
        'inventory_count',
        'capacity_per_unit',
        'is_required',
        'auto_add',
        'allow_quantity_change',
        'display_order',
        'category',
        'is_active',
    ];

    protected $casts = [
        // Note: 'name', 'description', 'short_description' are handled by Spatie's
        // HasTranslations trait - do NOT add them here as 'array' casts
        'person_type_prices' => 'array',
        'base_price_tnd' => 'decimal:2',
        'base_price_eur' => 'decimal:2',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'default_quantity' => 'integer',
        'inventory_count' => 'integer',
        'capacity_per_unit' => 'integer',
        'track_inventory' => 'boolean',
        'is_required' => 'boolean',
        'auto_add' => 'boolean',
        'allow_quantity_change' => 'boolean',
        'display_order' => 'integer',
        'is_active' => 'boolean',
        'pricing_type' => ExtraPricingType::class,
        'category' => ExtraCategory::class,
    ];

    public array $translatable = ['name', 'description', 'short_description'];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'vendor_id');
    }

    public function listings(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'listing_extras')
            ->using(ListingExtra::class)
            ->withPivot([
                'id',
                'override_price_tnd',
                'override_price_eur',
                'override_person_type_prices',
                'override_min_quantity',
                'override_max_quantity',
                'override_is_required',
                'display_order',
                'is_featured',
                'is_active',
            ])
            ->withTimestamps();
    }

    public function listingExtras(): HasMany
    {
        return $this->hasMany(ListingExtra::class);
    }

    public function bookingExtras(): HasMany
    {
        return $this->hasMany(BookingExtra::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(ExtraInventoryLog::class);
    }

    // =========================================================================
    // Pricing Methods
    // =========================================================================

    /**
     * Get base price for the given currency.
     */
    public function getPriceForCurrency(string $currency): float
    {
        return $currency === 'TND'
            ? (float) $this->base_price_tnd
            : (float) $this->base_price_eur;
    }

    /**
     * Get price for a specific person type and currency.
     */
    public function getPriceForPersonType(string $type, string $currency): float
    {
        $prices = $this->person_type_prices ?? [];
        $typeKey = strtolower($type);
        $currencyKey = strtolower($currency);

        if (isset($prices[$typeKey][$currencyKey])) {
            return (float) $prices[$typeKey][$currencyKey];
        }

        // Fallback to base price
        return $this->getPriceForCurrency($currency);
    }

    /**
     * Calculate total price based on pricing type and booking context.
     */
    public function calculateTotal(
        int $quantity,
        array $personTypeBreakdown,
        string $currency
    ): array {
        $totalGuests = array_sum($personTypeBreakdown);
        $unitPrice = $this->getPriceForCurrency($currency);

        return match ($this->pricing_type) {
            ExtraPricingType::PER_PERSON => [
                'subtotal' => $unitPrice * $totalGuests,
                'unit_price' => $unitPrice,
                'calculation' => "{$unitPrice} × {$totalGuests} guests",
            ],
            ExtraPricingType::PER_BOOKING => [
                'subtotal' => $unitPrice * $quantity,
                'unit_price' => $unitPrice,
                'calculation' => "{$unitPrice} × {$quantity}",
            ],
            ExtraPricingType::PER_UNIT => [
                'subtotal' => $unitPrice * $quantity,
                'unit_price' => $unitPrice,
                'calculation' => "{$unitPrice} × {$quantity} units",
            ],
            ExtraPricingType::PER_PERSON_TYPE => $this->calculatePerPersonType($personTypeBreakdown, $currency),
        };
    }

    /**
     * Calculate pricing for per_person_type.
     */
    protected function calculatePerPersonType(array $personTypeBreakdown, string $currency): array
    {
        $subtotal = 0;
        $breakdown = [];

        foreach ($personTypeBreakdown as $type => $count) {
            if ($count > 0) {
                $price = $this->getPriceForPersonType($type, $currency);
                $typeTotal = $price * $count;
                $subtotal += $typeTotal;
                $breakdown[$type] = [
                    'count' => $count,
                    'unit_price' => $price,
                    'total' => $typeTotal,
                ];
            }
        }

        return [
            'subtotal' => $subtotal,
            'unit_price' => null, // Not applicable for per_person_type
            'breakdown' => $breakdown,
            'calculation' => 'Variable by person type',
        ];
    }

    // =========================================================================
    // Inventory Methods
    // =========================================================================

    /**
     * Check if inventory tracking is enabled and has available stock.
     */
    public function hasAvailableInventory(int $quantity): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        return $this->inventory_count >= $quantity;
    }

    /**
     * Reserve inventory for a booking.
     */
    public function reserveInventory(int $quantity, ?Booking $booking = null, ?User $user = null): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        if (! $this->hasAvailableInventory($quantity)) {
            return false;
        }

        $previousCount = $this->inventory_count;
        $this->inventory_count -= $quantity;
        $this->save();

        // Log the change
        $this->inventoryLogs()->create([
            'booking_id' => $booking?->id,
            'change_type' => 'reserved',
            'quantity_change' => -$quantity,
            'previous_count' => $previousCount,
            'new_count' => $this->inventory_count,
            'created_by' => $user?->id,
        ]);

        return true;
    }

    /**
     * Release reserved inventory.
     */
    public function releaseInventory(int $quantity, ?Booking $booking = null, ?User $user = null): void
    {
        if (! $this->track_inventory) {
            return;
        }

        $previousCount = $this->inventory_count;
        $this->inventory_count += $quantity;
        $this->save();

        // Log the change
        $this->inventoryLogs()->create([
            'booking_id' => $booking?->id,
            'change_type' => 'released',
            'quantity_change' => $quantity,
            'previous_count' => $previousCount,
            'new_count' => $this->inventory_count,
            'created_by' => $user?->id,
        ]);
    }

    /**
     * Adjust inventory count manually.
     */
    public function adjustInventory(int $newCount, ?string $notes = null, ?User $user = null): void
    {
        $previousCount = $this->inventory_count ?? 0;
        $change = $newCount - $previousCount;

        $this->inventory_count = $newCount;
        $this->save();

        // Log the change
        $this->inventoryLogs()->create([
            'change_type' => $change >= 0 ? 'restock' : 'adjustment',
            'quantity_change' => $change,
            'previous_count' => $previousCount,
            'new_count' => $newCount,
            'notes' => $notes,
            'created_by' => $user?->id,
        ]);
    }

    // =========================================================================
    // Capacity Methods (for vehicles, equipment with capacity limits)
    // =========================================================================

    /**
     * Calculate how many units are needed for a given group size.
     *
     * Example: If capacity_per_unit = 4 (4-seat vehicle) and groupSize = 7,
     * returns ceil(7/4) = 2 units needed.
     */
    public function getUnitsNeeded(int $groupSize): int
    {
        if (! $this->capacity_per_unit || $this->capacity_per_unit <= 0) {
            return 1; // No capacity limit, 1 unit per booking
        }

        return (int) ceil($groupSize / $this->capacity_per_unit);
    }

    /**
     * Check if there's enough inventory capacity for a group.
     *
     * This considers capacity_per_unit to calculate how many units
     * are actually needed for the group size.
     */
    public function hasCapacityForGroup(int $groupSize): bool
    {
        if (! $this->track_inventory) {
            return true;
        }

        $unitsNeeded = $this->getUnitsNeeded($groupSize);

        return $this->hasAvailableInventory($unitsNeeded);
    }

    /**
     * Reserve inventory based on group capacity.
     *
     * Automatically calculates how many units are needed for the group.
     */
    public function reserveCapacityForGroup(int $groupSize, ?Booking $booking = null, ?User $user = null): bool
    {
        $unitsNeeded = $this->getUnitsNeeded($groupSize);

        return $this->reserveInventory($unitsNeeded, $booking, $user);
    }

    /**
     * Release inventory based on group capacity.
     */
    public function releaseCapacityForGroup(int $groupSize, ?Booking $booking = null, ?User $user = null): void
    {
        $unitsNeeded = $this->getUnitsNeeded($groupSize);
        $this->releaseInventory($unitsNeeded, $booking, $user);
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForVendor($query, string $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopeByCategory($query, ExtraCategory $category)
    {
        return $query->where('category', $category);
    }

    public function scopeWithInventory($query)
    {
        return $query->where('track_inventory', true);
    }

    public function scopeLowInventory($query, int $threshold = 5)
    {
        return $query->where('track_inventory', true)
            ->whereNotNull('inventory_count')
            ->where('inventory_count', '<=', $threshold);
    }
}
