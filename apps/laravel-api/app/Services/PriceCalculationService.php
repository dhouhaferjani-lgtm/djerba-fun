<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Listing;

class PriceCalculationService
{
    /**
     * Calculate the total price for a booking based on person type breakdown.
     *
     * @param Listing $listing The listing being booked
     * @param array $breakdown Person type breakdown: ["adult" => 2, "child" => 1, "infant" => 0]
     * @param string|null $currency Currency to use (TND or EUR). If null, defaults to EUR
     * @return array{breakdown: array, subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateTotal(Listing $listing, array $breakdown, ?string $currency = null): array
    {
        $pricing = $listing->pricing;
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];

        // Determine currency - prioritize parameter, then check for dual pricing
        if (!$currency) {
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

        // If personTypes is defined, use per-type pricing
        if (!empty($personTypes)) {
            foreach ($breakdown as $typeKey => $quantity) {
                if ($quantity <= 0) {
                    continue;
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
        } else {
            // Fallback: use base price for all guests
            $totalGuests = array_sum($breakdown);
            $subtotal = $basePrice * $totalGuests;

            foreach ($breakdown as $typeKey => $quantity) {
                if ($quantity <= 0) {
                    continue;
                }

                $details[] = [
                    'type' => $typeKey,
                    'label' => ['en' => ucfirst($typeKey), 'fr' => ucfirst($typeKey)],
                    'unitPrice' => $basePrice,
                    'quantity' => $quantity,
                    'total' => $basePrice * $quantity,
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
     * @param Listing $listing The listing being booked
     * @param int $quantity Total number of guests
     * @param string|null $currency Currency to use (TND or EUR). If null, defaults to EUR
     * @return array{subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateSimpleTotal(Listing $listing, int $quantity, ?string $currency = null): array
    {
        $pricing = $listing->pricing;

        // Determine currency
        if (!$currency) {
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
     * @param Listing $listing The listing
     * @param int $totalGuests Total number of guests
     * @param float $subtotal Subtotal before discount
     * @return float Discount amount
     */
    protected function calculateGroupDiscount(Listing $listing, int $totalGuests, float $subtotal): float
    {
        $pricing = $listing->pricing;
        $groupDiscount = $pricing['groupDiscount'] ?? $pricing['group_discount'] ?? null;

        if (!$groupDiscount) {
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
     * Get default person types for a listing.
     * Returns standard adult/child/infant types if not defined.
     *
     * @param Listing $listing The listing
     * @param string|null $currency Currency to use for default prices
     * @return array Default person types
     */
    public function getPersonTypes(Listing $listing, ?string $currency = null): array
    {
        $pricing = $listing->pricing;
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];

        if (!empty($personTypes)) {
            return $personTypes;
        }

        // Determine currency
        if (!$currency) {
            $currency = $pricing['currency'] ?? 'EUR';
        }

        // Return default person types based on base price for the selected currency
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
            [
                'key' => 'child',
                'label' => ['en' => 'Child (4-17)', 'fr' => 'Enfant (4-17)'],
                'price' => round($basePrice * 0.5), // 50% of adult price
                'tnd_price' => $currency === 'TND' ? round($basePrice * 0.5) : null,
                'eur_price' => $currency === 'EUR' ? round($basePrice * 0.5) : null,
                'minAge' => 4,
                'maxAge' => 17,
                'minQuantity' => 0,
                'maxQuantity' => null,
            ],
            [
                'key' => 'infant',
                'label' => ['en' => 'Infant (0-3)', 'fr' => 'Bébé (0-3)'],
                'price' => 0, // Free
                'tnd_price' => 0,
                'eur_price' => 0,
                'minAge' => 0,
                'maxAge' => 3,
                'minQuantity' => 0,
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
        // New dual-pricing format
        if ($currency === 'TND' && isset($pricing['tnd_price'])) {
            return (float) $pricing['tnd_price'];
        }

        if ($currency === 'EUR' && isset($pricing['eur_price'])) {
            return (float) $pricing['eur_price'];
        }

        // Fallback to old single-currency format
        if (isset($pricing['basePrice'])) {
            return (float) $pricing['basePrice'];
        }

        if (isset($pricing['base_price'])) {
            return (float) $pricing['base_price'];
        }

        if (isset($pricing['base'])) {
            return (float) $pricing['base'];
        }

        return 0;
    }

    /**
     * Get the person type price for the specified currency.
     */
    protected function getPersonTypePriceForCurrency(array $personType, string $currency): float
    {
        // New dual-pricing format
        if ($currency === 'TND' && isset($personType['tnd_price'])) {
            return (float) $personType['tnd_price'];
        }

        if ($currency === 'EUR' && isset($personType['eur_price'])) {
            return (float) $personType['eur_price'];
        }

        // Fallback to old single-currency format
        return (float) ($personType['price'] ?? 0);
    }
}
