<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Cart;
use App\Models\CartPayment;
use App\Services\CartCheckoutService;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CartCheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected CartCheckoutService $checkoutService
    ) {}

    /**
     * Initiate checkout for the current cart.
     */
    public function initiateCheckout(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');

        // Get cart
        $cart = $this->cartService->getActiveCart($user, $sessionId);

        // Diagnostic logging to track user authentication in checkout flow
        Log::info('CartCheckoutController::initiateCheckout - Starting checkout', [
            'has_user' => $user !== null,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'session_id' => $sessionId,
            'cart_id' => $cart?->id,
            'cart_user_id' => $cart?->user_id,
        ]);

        if (! $cart) {
            return response()->json([
                'message' => 'No active cart found',
            ], 404);
        }

        // Verify ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This cart does not belong to you',
            ], 403);
        }

        // Validate payment method
        $paymentMethodValue = $request->input('payment_method', 'card');

        if (! PaymentMethod::tryFrom($paymentMethodValue)) {
            return response()->json([
                'message' => 'Invalid payment method',
            ], 422);
        }

        $paymentMethod = PaymentMethod::from($paymentMethodValue);

        // Initiate checkout
        $result = $this->checkoutService->initiateCheckout($cart, $paymentMethod, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if (! $result['valid']) {
            return response()->json([
                'message' => 'Cart validation failed',
                'errors' => $result['errors'],
            ], 422);
        }

        return response()->json([
            'message' => 'Checkout initiated',
            'payment_id' => $result['payment']->id,
            'amount' => $result['payment']->amount,
            'currency' => $result['payment']->currency,
        ]);
    }

    /**
     * Process payment for a cart checkout.
     */
    public function processPayment(Request $request, CartPayment $payment): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');
        $cart = $payment->cart;

        // Verify ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'This payment does not belong to you',
            ], 403);
        }

        // Check payment status
        if ($payment->isSuccessful()) {
            return response()->json([
                'message' => 'Payment already completed',
                'bookings' => BookingResource::collection($payment->bookings),
            ]);
        }

        if ($payment->isFailed()) {
            return response()->json([
                'message' => 'This payment has failed. Please start a new checkout.',
            ], 422);
        }

        // Process payment
        $paymentData = $request->input('payment_data', []);
        $result = $this->checkoutService->processPayment($payment, $paymentData);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'],
                'success' => false,
            ], 422);
        }

        return response()->json([
            'message' => $result['message'],
            'success' => true,
            'bookings' => BookingResource::collection($result['bookings']),
        ]);
    }

    /**
     * Cancel a checkout in progress.
     */
    public function cancelCheckout(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');

        // Get cart
        $cart = Cart::where(function ($q) use ($user, $sessionId) {
            if ($user) {
                $q->where('user_id', $user->id);
            } elseif ($sessionId) {
                $q->where('session_id', $sessionId);
            }
        })
            ->where('status', Cart::STATUS_CHECKING_OUT)
            ->first();

        if (! $cart) {
            return response()->json([
                'message' => 'No checkout in progress',
            ], 404);
        }

        // Cancel checkout
        $this->checkoutService->cancelCheckout($cart);

        return response()->json([
            'message' => 'Checkout cancelled',
        ]);
    }

    /**
     * Get checkout status.
     */
    public function status(Request $request, CartPayment $payment): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->query('session_id');
        $cart = $payment->cart;

        // Verify ownership
        $isOwner = ($user && $cart->user_id === $user->id) ||
                   ($sessionId && $cart->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        $payment->load('bookings');

        return response()->json([
            'payment_id' => $payment->id,
            'status' => $payment->status->value,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'paid_at' => $payment->paid_at?->toIso8601String(),
            'bookings' => $payment->isSuccessful()
                ? BookingResource::collection($payment->bookings)
                : [],
        ]);
    }
}
