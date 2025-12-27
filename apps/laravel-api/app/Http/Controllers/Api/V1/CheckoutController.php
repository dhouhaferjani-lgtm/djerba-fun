<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BookingHold;
use App\Services\PricingVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Handles checkout-related operations including billing verification for PPP pricing.
 */
class CheckoutController extends Controller
{
    public function __construct(
        private readonly PricingVerificationService $pricingVerification
    ) {}

    /**
     * Verify billing address and detect any price changes due to PPP pricing.
     *
     * This endpoint is called when a customer enters their billing address during checkout.
     * It compares the original price (based on browsing country) with the final price
     * (based on billing country) and returns a disclosure message if they differ.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function verifyBilling(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'hold_id' => 'required|exists:booking_holds,id',
            'billing_address' => 'required|array',
            'billing_address.country_code' => 'required|string|size:2',
            'billing_address.city' => 'nullable|string|max:100',
            'billing_address.postal_code' => 'nullable|string|max:20',
            'billing_address.address_line1' => 'nullable|string|max:255',
            'billing_address.address_line2' => 'nullable|string|max:255',
        ]);

        $hold = BookingHold::findOrFail($validated['hold_id']);

        // Check if hold is still valid
        if ($hold->isExpired()) {
            return response()->json([
                'error' => 'Hold has expired',
                'code' => 'HOLD_EXPIRED',
            ], 410);
        }

        // Check if hold has been converted to a booking
        if ($hold->status === \App\Enums\HoldStatus::CONVERTED) {
            return response()->json([
                'error' => 'Hold has already been used',
                'code' => 'HOLD_CONVERTED',
            ], 410);
        }

        $verification = $this->pricingVerification->verifyBillingAddress(
            $hold,
            $validated['billing_address'],
            []
        );

        return response()->json([
            'success' => true,
            'pricing' => $verification,
        ]);
    }
}
