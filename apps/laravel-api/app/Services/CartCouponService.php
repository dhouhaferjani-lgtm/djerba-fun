<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use Illuminate\Support\Facades\Log;

class CartCouponService
{
    /**
     * Validate a coupon for a cart with partial application support.
     * Returns detailed breakdown of which items the coupon applies to.
     *
     * @param  Cart  $cart  The cart to validate against
     * @param  string  $couponCode  The coupon code to validate
     * @param  string|int|null  $userId  The user ID (if authenticated)
     * @return array{valid: bool, message: string, coupon_id?: string, discount_amount?: float, eligible_items?: array, excluded_items?: array, breakdown?: array, partial_application?: bool}
     */
    public function validateForCart(
        Cart $cart,
        string $couponCode,
        string|int|null $userId = null
    ): array {
        $coupon = Coupon::byCode($couponCode)->first();

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

        if ($userId && ! $coupon->isValidForUser($userId)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not available for your account.',
            ];
        }

        // Load cart items with listings
        $cart->load('items.listing');

        // Separate eligible and excluded items
        $eligibleItems = [];
        $excludedItems = [];
        $eligibleTotal = 0.0;

        foreach ($cart->items as $item) {
            $itemTotal = (float) $item->getTotal();
            $listingId = $item->listing_id;

            if ($coupon->isValidForListing($listingId)) {
                $eligibleItems[] = [
                    'id' => $item->id,
                    'listing_id' => $listingId,
                    'listing_title' => $item->listing?->title ?? 'Unknown',
                    'amount' => $itemTotal,
                ];
                $eligibleTotal += $itemTotal;
            } else {
                $excludedItems[] = [
                    'id' => $item->id,
                    'listing_id' => $listingId,
                    'listing_title' => $item->listing?->title ?? 'Unknown',
                    'amount' => $itemTotal,
                    'reason' => 'Coupon not valid for this listing',
                ];
            }
        }

        // No eligible items
        if (empty($eligibleItems)) {
            return [
                'valid' => false,
                'message' => 'This coupon is not valid for any items in your cart.',
            ];
        }

        // Check minimum order on eligible items total
        if (! $coupon->meetsMinimumOrder($eligibleTotal)) {
            return [
                'valid' => false,
                'message' => sprintf(
                    'Minimum order amount of %s required to use this coupon.',
                    number_format((float) $coupon->minimum_order, 2)
                ),
            ];
        }

        // Calculate discount on eligible items only
        $discountAmount = $coupon->calculateDiscount($eligibleTotal);

        // Calculate per-item breakdown (proportional distribution)
        $breakdown = [];
        foreach ($eligibleItems as $item) {
            $proportion = $item['amount'] / $eligibleTotal;
            $itemDiscount = round($discountAmount * $proportion, 2);
            $breakdown[] = [
                'item_id' => $item['id'],
                'listing_id' => $item['listing_id'],
                'original_amount' => $item['amount'],
                'discount_amount' => $itemDiscount,
                'final_amount' => $item['amount'] - $itemDiscount,
            ];
        }

        $partialApplication = ! empty($excludedItems);

        // Build user-friendly message
        $message = sprintf(
            'Coupon applied! You save %s',
            number_format($discountAmount, 2)
        );

        if ($partialApplication) {
            $eligibleCount = count($eligibleItems);
            $totalCount = count($eligibleItems) + count($excludedItems);
            $message = sprintf(
                'Coupon applied to %d of %d items. You save %s',
                $eligibleCount,
                $totalCount,
                number_format($discountAmount, 2)
            );
        }

        Log::info('Cart coupon validated', [
            'cart_id' => $cart->id,
            'coupon_id' => $coupon->id,
            'coupon_code' => $couponCode,
            'eligible_items' => count($eligibleItems),
            'excluded_items' => count($excludedItems),
            'eligible_total' => $eligibleTotal,
            'discount_amount' => $discountAmount,
            'partial_application' => $partialApplication,
        ]);

        return [
            'valid' => true,
            'coupon_id' => $coupon->id,
            'discount_amount' => $discountAmount,
            'message' => $message,
            'eligible_items' => $eligibleItems,
            'excluded_items' => $excludedItems,
            'breakdown' => $breakdown,
            'partial_application' => $partialApplication,
        ];
    }

    /**
     * Apply coupon to cart checkout and return application details.
     * Used by CartCheckoutService during initiateCheckout.
     *
     * @param  Cart  $cart  The cart
     * @param  string  $couponCode  The coupon code
     * @param  string|int|null  $userId  The user ID
     * @return array{coupon_id: string|null, coupon_code: string|null, discount_amount: float, application_details: array|null}
     */
    public function applyCoupon(
        Cart $cart,
        string $couponCode,
        string|int|null $userId = null
    ): array {
        $result = $this->validateForCart($cart, $couponCode, $userId);

        if (! $result['valid']) {
            Log::warning('Cart coupon application failed', [
                'cart_id' => $cart->id,
                'coupon_code' => $couponCode,
                'message' => $result['message'],
            ]);

            return [
                'coupon_id' => null,
                'coupon_code' => null,
                'discount_amount' => 0.0,
                'application_details' => null,
            ];
        }

        return [
            'coupon_id' => $result['coupon_id'],
            'coupon_code' => strtoupper($couponCode),
            'discount_amount' => $result['discount_amount'],
            'application_details' => [
                'eligible_items' => $result['eligible_items'],
                'excluded_items' => $result['excluded_items'],
                'breakdown' => $result['breakdown'],
                'partial_application' => $result['partial_application'],
            ],
        ];
    }
}
