<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExtraPricingType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ListingExtra extends Pivot
{
    use HasFactory, HasUuids;

    protected $table = 'listing_extras';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'listing_id',
        'extra_id',
        'override_price_tnd',
        'override_price_eur',
        'override_person_type_prices',
        'override_min_quantity',
        'override_max_quantity',
        'override_is_required',
        'available_for_slots',
        'available_for_person_types',
        'display_conditions',
        'display_order',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'override_price_tnd' => 'decimal:2',
        'override_price_eur' => 'decimal:2',
        'override_person_type_prices' => 'array',
        'override_min_quantity' => 'integer',
        'override_max_quantity' => 'integer',
        'override_is_required' => 'boolean',
        'available_for_slots' => 'array',
        'available_for_person_types' => 'array',
        'display_conditions' => 'array',
        'display_order' => 'integer',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class);
    }

    public function extra(): BelongsTo
    {
        return $this->belongsTo(Extra::class);
    }

    // =========================================================================
    // Effective Value Methods (use override if set, else extra's default)
    // =========================================================================

    /**
     * Get effective price for currency (override or extra default).
     */
    public function getEffectivePrice(string $currency): float
    {
        if ($currency === 'TND' && $this->override_price_tnd !== null) {
            return (float) $this->override_price_tnd;
        }

        if ($currency === 'EUR' && $this->override_price_eur !== null) {
            return (float) $this->override_price_eur;
        }

        return $this->extra->getPriceForCurrency($currency);
    }

    /**
     * Get effective person type prices.
     */
    public function getEffectivePersonTypePrices(): ?array
    {
        return $this->override_person_type_prices ?? $this->extra->person_type_prices;
    }

    /**
     * Get effective price for a specific person type.
     */
    public function getEffectivePersonTypePrice(string $type, string $currency): float
    {
        $prices = $this->getEffectivePersonTypePrices();
        $typeKey = strtolower($type);
        $currencyKey = strtolower($currency);

        if ($prices && isset($prices[$typeKey][$currencyKey])) {
            return (float) $prices[$typeKey][$currencyKey];
        }

        // Fallback to effective base price
        return $this->getEffectivePrice($currency);
    }

    /**
     * Get effective minimum quantity.
     */
    public function getEffectiveMinQuantity(): int
    {
        return $this->override_min_quantity ?? $this->extra->min_quantity ?? 0;
    }

    /**
     * Get effective maximum quantity.
     */
    public function getEffectiveMaxQuantity(): ?int
    {
        return $this->override_max_quantity ?? $this->extra->max_quantity;
    }

    /**
     * Get effective required status.
     */
    public function getEffectiveIsRequired(): bool
    {
        return $this->override_is_required ?? $this->extra->is_required ?? false;
    }

    // =========================================================================
    // Availability Methods
    // =========================================================================

    /**
     * Check if this extra is available for a specific slot.
     */
    public function isAvailableForSlot(?string $slotId): bool
    {
        if ($this->available_for_slots === null) {
            return true; // Available for all slots
        }

        if ($slotId === null) {
            return true; // No slot specified, consider available
        }

        return in_array($slotId, $this->available_for_slots);
    }

    /**
     * Check if this extra is available for a specific person type.
     */
    public function isAvailableForPersonType(string $type): bool
    {
        if ($this->available_for_person_types === null) {
            return true; // Available for all person types
        }

        return in_array(strtolower($type), array_map('strtolower', $this->available_for_person_types));
    }

    /**
     * Evaluate display conditions.
     */
    public function shouldDisplay(array $context): bool
    {
        if ($this->display_conditions === null) {
            return true;
        }

        $conditions = $this->display_conditions['conditions'] ?? [];
        $action = $this->display_conditions['action'] ?? 'show';

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '==';
            $value = $condition['value'] ?? null;

            if ($field === null || !isset($context[$field])) {
                continue;
            }

            $contextValue = $context[$field];
            $conditionMet = match ($operator) {
                '==' => $contextValue == $value,
                '!=' => $contextValue != $value,
                '>' => $contextValue > $value,
                '>=' => $contextValue >= $value,
                '<' => $contextValue < $value,
                '<=' => $contextValue <= $value,
                'in' => in_array($contextValue, (array) $value),
                default => false,
            };

            if (!$conditionMet) {
                return $action === 'hide'; // Condition not met, invert action
            }
        }

        return $action === 'show';
    }

    /**
     * Calculate total for this listing extra.
     */
    public function calculateTotal(
        int $quantity,
        array $personTypeBreakdown,
        string $currency
    ): array {
        $extra = $this->extra;
        $totalGuests = array_sum($personTypeBreakdown);
        $unitPrice = $this->getEffectivePrice($currency);

        return match ($extra->pricing_type) {
            ExtraPricingType::PER_PERSON => [
                'subtotal' => $unitPrice * $totalGuests,
                'unit_price' => $unitPrice,
                'quantity_applied' => $totalGuests,
                'calculation' => "{$unitPrice} × {$totalGuests} guests",
            ],
            ExtraPricingType::PER_BOOKING => [
                'subtotal' => $unitPrice * $quantity,
                'unit_price' => $unitPrice,
                'quantity_applied' => $quantity,
                'calculation' => "{$unitPrice} × {$quantity}",
            ],
            ExtraPricingType::PER_UNIT => [
                'subtotal' => $unitPrice * $quantity,
                'unit_price' => $unitPrice,
                'quantity_applied' => $quantity,
                'calculation' => "{$unitPrice} × {$quantity} units",
            ],
            ExtraPricingType::PER_PERSON_TYPE => $this->calculatePerPersonType($personTypeBreakdown, $currency),
        };
    }

    /**
     * Calculate pricing for per_person_type with overrides.
     */
    protected function calculatePerPersonType(array $personTypeBreakdown, string $currency): array
    {
        $subtotal = 0;
        $breakdown = [];

        foreach ($personTypeBreakdown as $type => $count) {
            if ($count > 0) {
                $price = $this->getEffectivePersonTypePrice($type, $currency);
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
            'unit_price' => null,
            'quantity_applied' => array_sum($personTypeBreakdown),
            'breakdown' => $breakdown,
            'calculation' => 'Variable by person type',
        ];
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForListing($query, string $listingId)
    {
        return $query->where('listing_id', $listingId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('created_at');
    }
}
