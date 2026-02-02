<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Listing;

class PriceCalculationService
{
    /**
     * Calculate the total price for a booking based on person type breakdown.
     *
     * @param  Listing  $listing  The listing being booked
     * @param  array  $breakdown  Person type breakdown: ["adult" => 2, "child" => 1, "infant" => 0]
     * @param  string|null  $currency  Currency to use (TND or EUR). If null, defaults to EUR
     * @return array{breakdown: array, subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateTotal(Listing $listing, array $breakdown, ?string $currency = null): array
    {
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
                // Get price for the selected currency
                $price = $this->getPersonTypePriceForCurrency($typeConfig, $currency);
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
     * @param  Listing  $listing  The listing being booked
     * @param  int  $quantity  Total number of guests
     * @param  string|null  $currency  Currency to use (TND or EUR). If null, defaults to EUR
     * @return array{subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateSimpleTotal(Listing $listing, int $quantity, ?string $currency = null): array
    {
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
