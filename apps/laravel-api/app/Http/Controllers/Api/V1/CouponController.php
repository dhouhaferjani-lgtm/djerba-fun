<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ValidateCouponRequest;
use App\Services\CartCouponService;
use App\Services\CartService;
use App\Services\CouponService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function __construct(
        private readonly CouponService $couponService,
        private readonly CartCouponService $cartCouponService,
        private readonly CartService $cartService
    ) {}

    /**
     * Validate a coupon code for a single listing.
     */
    public function validate(ValidateCouponRequest $request): JsonResponse
    {
        $result = $this->couponService->validate(
            code: $request->validated('code'),
            listingId: $request->validated('listing_id'),
            amount: (float) $request->validated('amount'),
            userId: $request->user()?->id
        );

        return response()->json($result, $result['valid'] ? 200 : 422);
    }

    /**
     * Validate a coupon code for a cart (with partial application support).
     */
    public function validateForCart(Request $request): JsonResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
            'session_id' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $sessionId = $request->input('session_id');

        // Get cart
        $cart = $this->cartService->getActiveCart($user, $sessionId);

        if (! $cart) {
            return response()->json([
                'valid' => false,
                'message' => 'No active cart found.',
            ], 404);
        }

        // Verify ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'valid' => false,
                'message' => 'This cart does not belong to you.',
            ], 403);
        }

        // Validate coupon for cart
        $result = $this->cartCouponService->validateForCart(
            $cart,
            $request->input('code'),
            $user?->id
        );

        return response()->json($result, $result['valid'] ? 200 : 422);
    }
}
