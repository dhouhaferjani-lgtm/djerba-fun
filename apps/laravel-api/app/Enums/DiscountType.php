<?php

declare(strict_types=1);

namespace App\Enums;

enum DiscountType: string
{
    case PERCENTAGE = 'percentage';
    case FIXED_AMOUNT = 'fixed_amount';

    /**
     * Get the label for the discount type.
     */
    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage',
            self::FIXED_AMOUNT => 'Fixed Amount',
        };
    }

    /**
     * Calculate the discount amount.
     */
    public function calculateDiscount(float $discountValue, float $orderAmount, ?float $maxDiscount = null): float
    {
        $discount = match ($this) {
            self::PERCENTAGE => $orderAmount * ($discountValue / 100),
            self::FIXED_AMOUNT => $discountValue,
        };

        // Apply maximum discount cap if set
        if ($maxDiscount !== null && $discount > $maxDiscount) {
            return $maxDiscount;
        }

        // Discount cannot exceed order amount
        return min($discount, $orderAmount);
    }
}
