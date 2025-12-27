<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Booking;
use App\Models\Coupon;

class CouponService
{
    /**
     * Validate a coupon code for a booking.
     */
    public function validate(
        string $code,
        string $listingId,
        float $amount,
        ?string $userId = null
    ): array {
        $coupon = Coupon::byCode($code)->first();

        if (! $coupon) {
            return [
                'valid' => false,
                'message' => 'Coupon code not found.',
            ];
        }

        if (! $coupon->isValid()) {
            return [
                'valid' => false,
                'message' => 'This coupon is no longer valid or has reached its usage limit.',
            ];
        }

        if (! $coupon->isValidForListing($listingId)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not valid for the selected listing.',
            ];
        }

        if ($userId && ! $coupon->isValidForUser($userId)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not available for your account.',
            ];
        }

        if (! $coupon->meetsMinimumOrder($amount)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Minimum order amount of %s %s required to use this coupon.',
                    number_format($coupon->minimum_order, 2),
                    'CAD'
                ),
            ];
        }

        $discountAmount = $coupon->calculateDiscount($amount);

        return [
            'valid' => true,
            'coupon_id' => $coupon->id,
            'discount_amount' => $discountAmount,
            'message' => sprintf(
                'Coupon applied! You save %s',
                number_format($discountAmount, 2)
            ),
        ];
    }

    /**
     * Apply a coupon to a booking.
     */
    public function apply(Coupon $coupon, Booking $booking): void
    {
        $discountAmount = $coupon->calculateDiscount($booking->total_amount);

        $booking->update([
            'coupon_id' => $coupon->id,
            'discount_amount' => $discountAmount,
        ]);

        $coupon->incrementUsage();
    }

    /**
     * Get discount amount for a coupon and order amount.
     */
    public function getDiscount(Coupon $coupon, float $amount): float
    {
        return $coupon->calculateDiscount($amount);
    }

    /**
     * Find a valid coupon by code.
     */
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::byCode($code)->active()->first();
    }
}
