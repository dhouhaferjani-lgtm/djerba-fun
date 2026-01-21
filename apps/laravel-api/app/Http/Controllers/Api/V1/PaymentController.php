<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentIntentResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\Payment\ClickToPayPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly BookingService $bookingService
    ) {}

    /**
     * Process payment for a booking.
     * Supports both authenticated users and guest checkout via session_id.
     * Handles both direct payments (mock, offline) and redirect-based payments (Clictopay).
     */
    public function processPayment(ProcessPaymentRequest $request, Booking $booking): JsonResponse
    {
        // Verify booking ownership: either authenticated user owns it, or guest has matching session_id
        $userId = $request->user()?->id;
        $sessionId = $request->input('session_id');

        $isOwner = ($userId && $booking->user_id === $userId) ||
                   ($sessionId && $booking->session_id === $sessionId);

        if (! $isOwner) {
            return response()->json([
                'message' => 'Unauthorized to pay for this booking.',
            ], 403);
        }

        // Check if booking can be paid
        if ($booking->isConfirmed() || $booking->isCancelled()) {
            return response()->json([
                'message' => 'This booking cannot be paid. Status: ' . $booking->status->label(),
            ], 422);
        }

        $paymentMethod = PaymentMethod::from($request->input('payment_method'));

        // Determine which gateway to use (with safe fallback for Clictopay)
        $gateway = match ($paymentMethod) {
            PaymentMethod::CARD,
            PaymentMethod::CLICK_TO_PAY => $this->getClickToPayGateway(),
            PaymentMethod::BANK_TRANSFER,
            PaymentMethod::CASH_ON_ARRIVAL,
            PaymentMethod::OFFLINE => $this->gatewayManager->gateway('offline'),
            PaymentMethod::FREE,
            PaymentMethod::MOCK => $this->gatewayManager->gateway('mock'),
        };

        // Create payment intent
        $intent = $gateway->createIntent($booking, [
            'payment_method' => $paymentMethod->value,
            'metadata' => [
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // Check if this is a redirect-based gateway (Clictopay)
        if ($gateway instanceof ClickToPayPaymentGateway) {
            $paymentUrl = $gateway->getPaymentUrl($intent);

            // If we have a payment URL and intent isn't failed, return redirect info
            if ($paymentUrl && $intent->status !== PaymentStatus::FAILED) {
                $booking->load(['listing', 'availabilitySlot', 'paymentIntents']);

                Log::info('Returning redirect URL for Clictopay payment', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'redirect_url' => $paymentUrl,
                ]);

                return response()->json([
                    'data' => new BookingResource($booking),
                    'payment_intent' => new PaymentIntentResource($intent),
                    'redirect_url' => $paymentUrl,
                    'requires_redirect' => true,
                    'message' => 'Redirect to payment gateway required.',
                ]);
            }

            // If no payment URL or registration failed, return error
            if (! $paymentUrl || $intent->status === PaymentStatus::FAILED) {
                $booking->load(['listing', 'availabilitySlot', 'paymentIntents']);

                Log::warning('Clictopay payment initialization failed', [
                    'booking_id' => $booking->id,
                    'intent_id' => $intent->id,
                    'intent_status' => $intent->status->value,
                    'metadata' => $intent->metadata,
                ]);

                return response()->json([
                    'data' => new BookingResource($booking),
                    'payment_intent' => new PaymentIntentResource($intent),
                    'message' => 'Payment gateway initialization failed. Please try again or choose a different payment method.',
                ], 422);
            }
        }

        // Process non-redirect payment (mock, offline, etc.)
        $intent = $gateway->processPayment($intent, $request->input('payment_data', []));

        // If payment succeeded, confirm the booking
        if ($intent->isSuccessful()) {
            $booking = $this->bookingService->confirmPayment($booking, $intent);
        }

        $booking->load(['listing', 'availabilitySlot', 'paymentIntents']);

        return response()->json([
            'data' => new BookingResource($booking),
            'payment_intent' => new PaymentIntentResource($intent),
            'message' => $intent->isSuccessful()
                ? 'Payment processed successfully. Booking confirmed!'
                : 'Payment is pending confirmation.',
        ]);
    }

    /**
     * Get Click to Pay gateway if enabled and configured, otherwise fall back to mock.
     *
     * This ensures checkout never breaks even if Clictopay is:
     * - Disabled in the database
     * - Missing credentials
     * - Misconfigured
     *
     * Safe fallback preserves existing functionality until Clictopay is properly set up.
     */
    private function getClickToPayGateway(): PaymentGateway
    {
        // Check if clicktopay is enabled and properly configured
        $clicktopayModel = \App\Models\PaymentGateway::where('driver', 'clicktopay')
            ->where('is_enabled', true)
            ->first();

        if ($clicktopayModel) {
            $config = $clicktopayModel->configuration ?? [];

            // Only use clicktopay if credentials are configured
            if (! empty($config['username']) && ! empty($config['password'])) {
                Log::info('Using Clictopay gateway for payment', [
                    'test_mode' => $clicktopayModel->test_mode,
                ]);

                return $this->gatewayManager->gateway('clicktopay');
            }

            Log::warning('Clictopay gateway enabled but missing credentials, falling back to mock');
        }

        // Fall back to mock gateway (safe default)
        Log::debug('Using mock gateway for click_to_pay (Clictopay not configured)');

        return $this->gatewayManager->gateway('mock');
    }

    /**
     * Get payment status for a booking.
     */
    public function paymentStatus(Booking $booking): JsonResponse
    {
        // Ensure the booking belongs to the authenticated user
        Gate::authorize('view', $booking);

        $latestIntent = $booking->latestPaymentIntent();

        if (! $latestIntent) {
            return response()->json([
                'message' => 'No payment intent found for this booking.',
            ], 404);
        }

        // Refresh status from gateway if needed
        $gateway = $this->gatewayManager->gateway($latestIntent->gateway);
        $currentStatus = $gateway->getStatus($latestIntent);

        // Update if status changed
        if ($currentStatus !== $latestIntent->status) {
            $latestIntent->update(['status' => $currentStatus]);
        }

        return response()->json([
            'data' => new PaymentIntentResource($latestIntent),
        ]);
    }

    /**
     * Get available payment methods based on enabled gateways.
     */
    public function availableMethods(): JsonResponse
    {
        $methods = $this->gatewayManager->getAvailablePaymentMethods();

        return response()->json([
            'data' => $methods,
        ]);
    }
}
