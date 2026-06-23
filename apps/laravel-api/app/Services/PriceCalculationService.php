<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AvailabilitySlot;
use App\Models\Listing;

class PriceCalculationService
{
    /**
     * Calculate the total price for a booking based on person type breakdown.
     *
     * When a slot is supplied AND it carries `price_overrides`, each
     * overridden person-type uses the slot's price; person-types NOT listed
     * in the override fall back to the listing's pricing (lenient per-key
     * merge — see AvailabilitySlot::getEffectivePersonTypePrices()).
     *
     * Passing $slot=null (or omitting it) reproduces the pre-feature
     * listing-only behaviour bit-for-bit — the regression guard.
     *
     * @param  Listing  $listing  The listing being booked
     * @param  array  $breakdown  Person type breakdown: ["adult" => 2, "child" => 1, "infant" => 0]
     * @param  string|null  $currency  Currency to use (TND or EUR). If null, defaults to EUR
     * @param  AvailabilitySlot|null  $slot  Optional slot whose price_overrides take priority
     * @return array{breakdown: array, subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateTotal(
        Listing $listing,
        array $breakdown,
        ?string $currency = null,
        ?AvailabilitySlot $slot = null,
    ): array {
        // Tiered listings price purely by headcount — collapse the per-type
        // breakdown to a single traveller count and delegate. Returns the same
        // shape callers expect (breakdown/subtotal/discount/total/currency/totalGuests).
        if ($this->isTieredPricing($listing)) {
            $headcount = (int) array_sum(array_map('intval', $breakdown));
            $tiered = $this->calculateTieredTotal($listing, $headcount, $currency, $slot);

            return [
                'breakdown' => $headcount > 0 ? [[
                    'type' => 'traveler',
                    'label' => ['en' => 'Travelers', 'fr' => 'Voyageurs'],
                    'unitPrice' => round($tiered['total'] / $headcount, 2),
                    'quantity' => $headcount,
                    'total' => $tiered['total'],
                ]] : [],
                'subtotal' => $tiered['subtotal'],
                'discount' => $tiered['discount'],
                'total' => $tiered['total'],
                'currency' => $tiered['currency'],
                'totalGuests' => $headcount,
            ];
        }

        $pricing = $listing->pricing;

        // Determine currency - prioritize parameter, then check for dual pricing
        if (! $currency) {
            $currency = $pricing['currency'] ?? 'EUR';
        }

        // Get base price for the selected currency
        $basePrice = $this->getPriceForCurrency($pricing, $currency);

        // If numeric string, convert to number
        if (is_string($basePrice)) {
            $basePrice = (float) $basePrice;
        }

        $subtotal = 0;
        $details = [];
        $totalGuests = 0;

        // Get person types - only those explicitly defined by vendor
        $personTypes = $this->getPersonTypes($listing, $currency);
        $allowedKeys = collect($personTypes)->pluck('key')->toArray();

        // Resolve the slot-effective per-person-type price map (or null when no
        // slot / no override). Callers that don't pass a slot get today's
        // listing-only behaviour — the regression guard.
        $effectivePrices = $slot
            ? $slot->getEffectivePersonTypePrices($currency, $personTypes)
            : null;

        // Validate and calculate pricing for each person type in the breakdown
        foreach ($breakdown as $typeKey => $quantity) {
            if ($quantity <= 0) {
                continue;
            }

            // Check if this person type is allowed for this listing
            if (! in_array($typeKey, $allowedKeys)) {
                // Person type not defined by vendor - skip it and log warning
                \Log::warning('Booking attempted with undefined person type', [
                    'listing_id' => $listing->id,
                    'listing_slug' => $listing->slug,
                    'attempted_type' => $typeKey,
                    'allowed_types' => $allowedKeys,
                    'quantity' => $quantity,
                ]);
                continue; // Skip undefined person types (vendor didn't configure them)
            }

            $typeConfig = collect($personTypes)->firstWhere('key', $typeKey);

            if ($typeConfig) {
                // Slot override wins per-key when present; otherwise fall back
                // to the listing's per-currency price helper (existing path).
                $price = ($effectivePrices !== null && array_key_exists($typeKey, $effectivePrices))
                    ? (float) $effectivePrices[$typeKey]
                    : $this->getPersonTypePriceForCurrency($typeConfig, $currency);
                $label = $typeConfig['label'] ?? ['en' => ucfirst($typeKey), 'fr' => ucfirst($typeKey)];
                $lineTotal = $price * $quantity;
                $subtotal += $lineTotal;
                $totalGuests += $quantity;

                $details[] = [
                    'type' => $typeKey,
                    'label' => $label,
                    'unitPrice' => $price,
                    'quantity' => $quantity,
                    'total' => $lineTotal,
                ];
            }
        }

        // Apply group discount if applicable
        $discount = $this->calculateGroupDiscount($listing, $totalGuests, $subtotal);

        return [
            'breakdown' => $details,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'currency' => $currency,
            'totalGuests' => $totalGuests,
        ];
    }

    /**
     * Calculate simple total without breakdown (backward compatible).
     *
     * When a slot with overrides is supplied, the first listed person-type's
     * effective slot price stands in for the base price (typically "adult").
     *
     * @param  Listing  $listing  The listing being booked
     * @param  int  $quantity  Total number of guests
     * @param  string|null  $currency  Currency to use (TND or EUR). If null, defaults to EUR
     * @param  AvailabilitySlot|null  $slot  Optional slot whose price_overrides take priority
     * @return array{subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateSimpleTotal(
        Listing $listing,
        int $quantity,
        ?string $currency = null,
        ?AvailabilitySlot $slot = null,
    ): array {
        // Tiered listings use the headcount path — this is the primary tiered
        // route (the booking flow posts `guests`/quantity, not person types).
        if ($this->isTieredPricing($listing)) {
            return $this->calculateTieredTotal($listing, $quantity, $currency, $slot);
        }

        $pricing = $listing->pricing;

        // Determine currency
        if (! $currency) {
            $currency = $pricing['currency'] ?? 'EUR';
        }

        // Get base price for the selected currency
        $basePrice = $this->getPriceForCurrency($pricing, $currency);

        // If numeric string, convert to number
        if (is_string($basePrice)) {
            $basePrice = (float) $basePrice;
        }

        // Slot-level override on the listing's first person-type (if any) wins.
        if ($slot
            && is_array($pricing['person_types'] ?? null)
            && ! empty($pricing['person_types'])
        ) {
            $effective = $slot->getEffectivePersonTypePrices($currency, $pricing['person_types']);
            $firstKey = $pricing['person_types'][0]['key'] ?? null;

            if ($firstKey !== null && array_key_exists($firstKey, $effective)) {
                $basePrice = (float) $effective[$firstKey];
            }
        }

        $subtotal = $basePrice * $quantity;
        $discount = $this->calculateGroupDiscount($listing, $quantity, $subtotal);

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, $subtotal - $discount),
            'currency' => $currency,
        ];
    }

    /**
     * Calculate the total for a TIERED (positional) listing by headcount.
     *
     * The vendor supplies cumulative group totals T[1..K] (the full price for
     * a group of 1, 2, … K travellers). For a group of N:
     *
     *     total(N) = floor(N / K) * T[K] + T[N mod K]      (T[0] = 0)
     *
     * Larger groups repeat the pattern. There is NO separate group discount —
     * the tier table IS the discount mechanism (e.g. a flat T[3]==T[2] makes
     * the 3rd traveller free). $slot is accepted for signature parity with the
     * flat methods but is intentionally ignored: per-slot price overrides are
     * not supported for tiered listings in v1.
     *
     * @return array{subtotal: float, discount: float, total: float, currency: string}
     */
    public function calculateTieredTotal(
        Listing $listing,
        int $quantity,
        ?string $currency = null,
        ?AvailabilitySlot $slot = null,
    ): array {
        $pricing = $listing->pricing ?? [];

        if (! $currency) {
            $currency = $pricing['currency'] ?? 'EUR';
        }

        $tiers = $this->getTierTotals($pricing, $currency); // [1 => T1, 2 => T2, ...]
        $k = count($tiers);

        if ($k === 0 || $quantity <= 0) {
            return [
                'subtotal' => 0.0,
                'discount' => 0.0,
                'total' => 0.0,
                'currency' => $currency,
            ];
        }

        $largest = $tiers[$k];                      // T[K]
        $fullCycles = intdiv($quantity, $k);
        $remainder = $quantity % $k;
        $remainderTotal = $remainder === 0 ? 0.0 : ($tiers[$remainder] ?? 0.0);

        $subtotal = round($fullCycles * $largest + $remainderTotal, 2);

        return [
            'subtotal' => $subtotal,
            'discount' => 0.0,
            'total' => max(0, $subtotal),
            'currency' => $currency,
        ];
    }

    /**
     * Whether the listing uses the optional tiered pricing strategy.
     * Absent / any non-'tiered' value === flat (the zero-regression default).
     */
    public function isTieredPricing(Listing $listing): bool
    {
        $pricing = $listing->pricing ?? [];
        $strategy = $pricing['pricing_strategy'] ?? $pricing['pricingStrategy'] ?? 'flat';

        return $strategy === 'tiered';
    }

    /**
     * Resolve the cumulative tier totals for a currency as a 1-indexed map
     * [1 => T1, 2 => T2, …]. Tiers are sorted by `position` and re-indexed by
     * order, so malformed/non-contiguous positions degrade gracefully.
     * Supports snake_case (DB) and camelCase (API) keys.
     */
    private function getTierTotals(array $pricing, string $currency): array
    {
        $tiers = $pricing['tiers'] ?? [];

        if (! is_array($tiers) || $tiers === []) {
            return [];
        }

        $tiers = array_values(array_filter($tiers, 'is_array'));
        usort($tiers, fn ($a, $b) => ((int) ($a['position'] ?? 0)) <=> ((int) ($b['position'] ?? 0)));

        $isTnd = strtoupper($currency) === 'TND';
        $snakeKey = $isTnd ? 'tnd_total' : 'eur_total';
        $camelKey = $isTnd ? 'tndTotal' : 'eurTotal';

        $totals = [];
        $index = 1;

        foreach ($tiers as $tier) {
            $value = $tier[$snakeKey] ?? $tier[$camelKey] ?? 0;
            $totals[$index] = (float) $value;
            $index++;
        }

        return $totals;
    }

    /**
     * Calculate group discount based on listing configuration.
     *
     * @param  Listing  $listing  The listing
     * @param  int  $totalGuests  Total number of guests
     * @param  float  $subtotal  Subtotal before discount
     * @return float Discount amount
     */
    protected function calculateGroupDiscount(Listing $listing, int $totalGuests, float $subtotal): float
    {
        $pricing = $listing->pricing;
        $groupDiscount = $pricing['groupDiscount'] ?? $pricing['group_discount'] ?? null;

        if (! $groupDiscount) {
            return 0;
        }

        $minSize = $groupDiscount['minSize'] ?? $groupDiscount['min_size'] ?? PHP_INT_MAX;
        $discountPercent = $groupDiscount['discountPercent'] ?? $groupDiscount['discount_percent'] ?? 0;

        if ($totalGuests >= $minSize && $discountPercent > 0) {
            return $subtotal * ($discountPercent / 100);
        }

        return 0;
    }

    /**
     * Get person types for a listing.
     * Returns only the person types explicitly defined by the vendor.
     * If none defined, returns ONLY adult as default (vendor must explicitly add child/infant).
     *
     * @param  Listing  $listing  The listing
     * @param  string|null  $currency  Currency to use for default prices
     * @return array Person types defined for this listing
     */
    public function getPersonTypes(Listing $listing, ?string $currency = null): array
    {
        $pricing = $listing->pricing;
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];

        // If vendor has defined custom person types, return those
        if (! empty($personTypes)) {
            return $personTypes;
        }

        // Fallback: If no person types defined (legacy data), return ONLY adult
        // Vendors must explicitly define child/infant if they want to accept them
        if (! $currency) {
            $currency = $pricing['currency'] ?? 'EUR';
        }

        $basePrice = $this->getPriceForCurrency($pricing, $currency);

        if (is_string($basePrice)) {
            $basePrice = (float) $basePrice;
        }

        return [
            [
                'key' => 'adult',
                'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
                'price' => $basePrice,
                'tnd_price' => $currency === 'TND' ? $basePrice : null,
                'eur_price' => $currency === 'EUR' ? $basePrice : null,
                'minAge' => 18,
                'maxAge' => null,
                'minQuantity' => 1,
                'maxQuantity' => null,
            ],
        ];
    }

    /**
     * Get the base price for the specified currency from the pricing structure.
     *
     * Supports both old single-currency format and new dual-currency format.
     */
    protected function getPriceForCurrency(array $pricing, string $currency): float
    {
        // Handle person_types pricing structure (new format)
        if (isset($pricing['person_types']) && ! empty($pricing['person_types'])) {
            $firstType = $pricing['person_types'][0] ?? [];

            if ($currency === 'TND' && isset($firstType['tnd_price'])) {
                return (float) $firstType['tnd_price'];
            }

            if ($currency === 'EUR' && isset($firstType['eur_price'])) {
                return (float) $firstType['eur_price'];
            }
        }

        // Direct dual-pricing format (snake_case)
        if ($currency === 'TND' && isset($pricing['tnd_price'])) {
            return (float) $pricing['tnd_price'];
        }

        if ($currency === 'EUR' && isset($pricing['eur_price'])) {
            return (float) $pricing['eur_price'];
        }

        // Fallback to old single-currency format
        return (float) ($pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? 0);
    }

    /**
     * Get the person type price for the specified currency.
     */
    protected function getPersonTypePriceForCurrency(array $personType, string $currency): float
    {
        // Check snake_case keys (database format)
        if ($currency === 'TND' && isset($personType['tnd_price'])) {
            return (float) $personType['tnd_price'];
        }

        if ($currency === 'EUR' && isset($personType['eur_price'])) {
            return (float) $personType['eur_price'];
        }

        // Check camelCase keys (API format)
        if ($currency === 'TND' && isset($personType['tndPrice'])) {
            return (float) $personType['tndPrice'];
        }

        if ($currency === 'EUR' && isset($personType['eurPrice'])) {
            return (float) $personType['eurPrice'];
        }

        // Check displayPrice (computed by API)
        if (isset($personType['displayPrice'])) {
            return (float) $personType['displayPrice'];
        }

        // Fallback to generic price
        return (float) ($personType['price'] ?? 0);
    }
}
