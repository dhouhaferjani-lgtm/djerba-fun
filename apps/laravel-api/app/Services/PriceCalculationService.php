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
     * @return array{breakdown: array, subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateTotal(Listing $listing, array $breakdown): array
    {
        $pricing = $listing->pricing;
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];
        $currency = $pricing['currency'] ?? 'EUR';
        $basePrice = $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? 0;

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
                    $price = $typeConfig['price'] ?? 0;
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
     * @return array{subtotal: int|float, discount: int|float, total: int|float, currency: string}
     */
    public function calculateSimpleTotal(Listing $listing, int $quantity): array
    {
        $pricing = $listing->pricing;
        $currency = $pricing['currency'] ?? 'EUR';
        $basePrice = $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? 0;

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
     * @return array Default person types
     */
    public function getPersonTypes(Listing $listing): array
    {
        $pricing = $listing->pricing;
        $personTypes = $pricing['personTypes'] ?? $pricing['person_types'] ?? [];

        if (!empty($personTypes)) {
            return $personTypes;
        }

        // Return default person types based on base price
        $basePrice = $pricing['basePrice'] ?? $pricing['base_price'] ?? $pricing['base'] ?? 0;
        if (is_string($basePrice)) {
            $basePrice = (float) $basePrice;
        }

        return [
            [
                'key' => 'adult',
                'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
                'price' => $basePrice,
                'minAge' => 18,
                'maxAge' => null,
                'minQuantity' => 1,
                'maxQuantity' => null,
            ],
            [
                'key' => 'child',
                'label' => ['en' => 'Child (4-17)', 'fr' => 'Enfant (4-17)'],
                'price' => round($basePrice * 0.5), // 50% of adult price
                'minAge' => 4,
                'maxAge' => 17,
                'minQuantity' => 0,
                'maxQuantity' => null,
            ],
            [
                'key' => 'infant',
                'label' => ['en' => 'Infant (0-3)', 'fr' => 'Bébé (0-3)'],
                'price' => 0, // Free
                'minAge' => 0,
                'maxAge' => 3,
                'minQuantity' => 0,
                'maxQuantity' => null,
            ],
        ];
    }
}
