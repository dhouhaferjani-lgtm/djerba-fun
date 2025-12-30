<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProcessPaymentRequest;
use App\Http\Resources\BookingResource;
use App\Http\Resources\PaymentIntentResource;
use App\Models\Booking;
use App\Services\BookingService;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentGatewayManager $gatewayManager,
        private readonly BookingService $bookingService
    ) {}

    /**
     * Process payment for a booking.
     * Supports both authenticated users and guest checkout via session_id.
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

        // Determine which gateway to use
        $gateway = match ($paymentMethod) {
            PaymentMethod::CARD,
            PaymentMethod::CLICK_TO_PAY => $this->gatewayManager->gateway('mock'),
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

        // Process the payment
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
