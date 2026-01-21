<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PaymentIntent;
use App\Services\BookingService;
use App\Services\Payment\ClickToPayPaymentGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Handle Clictopay payment callbacks.
 *
 * This controller handles the redirect from Clictopay after the user
 * completes (or cancels) their payment on the Clictopay hosted page.
 */
class ClictopayCallbackController extends Controller
{
    public function __construct(
        private readonly ClickToPayPaymentGateway $gateway,
        private readonly BookingService $bookingService
    ) {}

    /**
     * Handle Clictopay redirect callback.
     *
     * This endpoint is called when Clictopay redirects the user back
     * after payment completion or cancellation.
     *
     * Flow:
     * 1. Verify the payment status with Clictopay API
     * 2. If successful, confirm the booking
     * 3. Redirect user to appropriate frontend page
     */
    public function callback(Request $request, PaymentIntent $intent): RedirectResponse
    {
        Log::info('Clictopay callback received', [
            'intent_id' => $intent->id,
            'booking_id' => $intent->booking_id,
            'query_params' => $request->query(),
        ]);

        // Get frontend URL from config
        $frontendUrl = config('app.frontend_url', 'http://localhost:3000');

        try {
            // Process payment (verify status with Clictopay API)
            $intent = $this->gateway->processPayment($intent, $request->all());

            // If payment succeeded, confirm the booking
            if ($intent->isSuccessful()) {
                $booking = $intent->booking;

                // Confirm the booking (sends confirmation email, etc.)
                $this->bookingService->confirmPayment($booking, $intent);

                Log::info('Clictopay payment successful, booking confirmed', [
                    'intent_id' => $intent->id,
                    'booking_id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                ]);

                // Redirect to success page
                return redirect()->away(
                    $frontendUrl . '/checkout/success?' . http_build_query([
                        'booking' => $booking->booking_number,
                        'status' => 'confirmed',
                    ])
                );
            }

            // Payment failed or pending
            $reason = $this->getFailureReason($intent);

            Log::warning('Clictopay payment not successful', [
                'intent_id' => $intent->id,
                'status' => $intent->status->value,
                'reason' => $reason,
            ]);

            return redirect()->away(
                $frontendUrl . '/checkout/failure?' . http_build_query([
                    'intent' => $intent->id,
                    'reason' => $reason,
                    'booking' => $intent->booking->booking_number ?? null,
                ])
            );
        } catch (\Exception $e) {
            Log::error('Clictopay callback processing failed', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->away(
                $frontendUrl . '/checkout/failure?' . http_build_query([
                    'intent' => $intent->id,
                    'reason' => 'processing_error',
                    'booking' => $intent->booking->booking_number ?? null,
                ])
            );
        }
    }

    /**
     * Get a user-friendly failure reason from the payment intent.
     */
    private function getFailureReason(PaymentIntent $intent): string
    {
        $metadata = $intent->metadata ?? [];

        // Check for specific error codes
        if (isset($metadata['order_status_code'])) {
            return match ($metadata['order_status_code']) {
                0 => 'payment_not_completed',
                3 => 'payment_reversed',
                4 => 'payment_refunded',
                5 => 'payment_declined',
                6 => 'payment_pending_verification',
                default => 'payment_failed',
            };
        }

        // Check for registration errors
        if (isset($metadata['registration_error'])) {
            return 'gateway_error';
        }

        // Check for verification errors
        if (isset($metadata['verification_error'])) {
            return 'verification_error';
        }

        return 'payment_failed';
    }
}
