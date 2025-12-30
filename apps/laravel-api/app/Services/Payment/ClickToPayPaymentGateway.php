<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ClickToPayPaymentGateway implements PaymentGateway
{
    /**
     * Configuration for the Click to Pay gateway.
     */
    private ?array $config = null;

    /**
     * Create a payment intent for the booking.
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent
    {
        $this->loadConfiguration();

        return PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'payment_method' => $options['payment_method'] ?? 'card',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'clicktopay',
            'gateway_id' => 'ctp_' . Str::random(20),
            'metadata' => array_merge($options['metadata'] ?? [], [
                'created_at' => now()->toIso8601String(),
                'merchant_id' => $this->config['merchant_id'] ?? null,
                'test_mode' => $this->isTestMode(),
            ]),
        ]);
    }

    /**
     * Process payment for the intent.
     *
     * This method integrates with the Click to Pay (Visa) API.
     * Current implementation is a placeholder for future API integration.
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent
    {
        $this->loadConfiguration();

        try {
            // TODO: Integrate with Click to Pay API
            // This is a placeholder implementation until the actual API is integrated
            //
            // Expected flow:
            // 1. Create payment session with Click to Pay API
            // 2. Get payment URL or session ID
            // 3. Handle redirect or modal for user payment
            // 4. Process webhook callback
            // 5. Verify payment signature
            // 6. Update payment intent status

            if ($this->isTestMode()) {
                // Test mode: simulate successful payment after delay
                sleep(1);

                $intent->update([
                    'status' => PaymentStatus::SUCCEEDED,
                    'paid_at' => now(),
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'processed_at' => now()->toIso8601String(),
                        'test_transaction_id' => 'ctp_test_' . Str::random(24),
                        'payment_method_type' => $data['payment_method_type'] ?? 'visa',
                        'last_4_digits' => $data['card_last_4'] ?? '4242',
                    ]),
                ]);

                Log::info('Click to Pay test payment processed', [
                    'intent_id' => $intent->id,
                    'booking_id' => $intent->booking_id,
                    'amount' => $intent->amount,
                ]);
            } else {
                // Production mode placeholder
                // In production, this would call the actual Click to Pay API
                $intent->update([
                    'status' => PaymentStatus::PENDING,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'pending_reason' => 'Click to Pay API integration pending',
                        'requested_at' => now()->toIso8601String(),
                    ]),
                ]);

                Log::warning('Click to Pay production API not yet implemented', [
                    'intent_id' => $intent->id,
                    'booking_id' => $intent->booking_id,
                ]);
            }

            return $intent->fresh();
        } catch (\Exception $e) {
            Log::error('Click to Pay payment processing failed', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            $intent->update([
                'status' => PaymentStatus::FAILED,
                'metadata' => array_merge($intent->metadata ?? [], [
                    'error' => $e->getMessage(),
                    'failed_at' => now()->toIso8601String(),
                ]),
            ]);

            return $intent->fresh();
        }
    }

    /**
     * Refund a payment intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to refund
     * @param  int|null  $amount  Amount to refund in cents (null for full refund)
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        $this->loadConfiguration();

        $refundAmount = $amount ?? (int) ($intent->amount * 100);

        try {
            // TODO: Integrate with Click to Pay refund API
            // Expected flow:
            // 1. Call Click to Pay refund endpoint
            // 2. Verify refund request
            // 3. Process refund (may take several days)
            // 4. Update payment intent status

            if ($this->isTestMode()) {
                // Test mode: simulate successful refund
                $intent->update([
                    'status' => $refundAmount === (int) ($intent->amount * 100)
                        ? PaymentStatus::REFUNDED
                        : PaymentStatus::PARTIALLY_REFUNDED,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'refunded_at' => now()->toIso8601String(),
                        'refund_amount' => $refundAmount / 100,
                        'test_refund_id' => 'ctp_rfnd_test_' . Str::random(24),
                        'refund_status' => 'completed',
                    ]),
                ]);

                Log::info('Click to Pay test refund processed', [
                    'intent_id' => $intent->id,
                    'refund_amount' => $refundAmount / 100,
                ]);
            } else {
                // Production mode placeholder
                $intent->update([
                    'status' => PaymentStatus::PENDING,
                    'metadata' => array_merge($intent->metadata ?? [], [
                        'refund_requested_at' => now()->toIso8601String(),
                        'refund_amount' => $refundAmount / 100,
                        'refund_status' => 'pending',
                    ]),
                ]);

                Log::warning('Click to Pay production refund API not yet implemented', [
                    'intent_id' => $intent->id,
                    'refund_amount' => $refundAmount / 100,
                ]);
            }

            return $intent->fresh();
        } catch (\Exception $e) {
            Log::error('Click to Pay refund failed', [
                'intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get the current status of a payment intent.
     */
    public function getStatus(PaymentIntent $intent): PaymentStatus
    {
        // TODO: Query Click to Pay API for current payment status
        // This would be useful for checking payment status asynchronously
        return $intent->status;
    }

    /**
     * Load configuration from the database.
     */
    private function loadConfiguration(): void
    {
        if ($this->config === null) {
            $gateway = \App\Models\PaymentGateway::where('driver', 'clicktopay')->first();
            $this->config = $gateway?->configuration ?? [];
        }
    }

    /**
     * Check if gateway is in test mode.
     */
    private function isTestMode(): bool
    {
        $gateway = \App\Models\PaymentGateway::where('driver', 'clicktopay')->first();

        return $gateway?->test_mode ?? true;
    }

    /**
     * Verify webhook signature from Click to Pay.
     * This method should be called in the webhook controller.
     *
     * @param  string  $payload  The raw webhook payload
     * @param  string  $signature  The signature from the webhook headers
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        $this->loadConfiguration();

        // TODO: Implement actual Click to Pay signature verification
        // Expected flow:
        // 1. Get shared secret from configuration
        // 2. Compute HMAC signature of payload
        // 3. Compare with provided signature
        // 4. Return true if valid, false otherwise

        $sharedSecret = $this->config['shared_secret'] ?? '';

        if (empty($sharedSecret)) {
            Log::warning('Click to Pay shared secret not configured');

            return false;
        }

        // Placeholder signature verification
        $expectedSignature = hash_hmac('sha256', $payload, $sharedSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Handle webhook from Click to Pay.
     * This method should be called in the webhook controller.
     *
     * @param  array  $payload  The decoded webhook payload
     */
    public function handleWebhook(array $payload): void
    {
        // TODO: Implement Click to Pay webhook handling
        // Expected events:
        // - payment.succeeded
        // - payment.failed
        // - refund.completed
        // - refund.failed

        $eventType = $payload['event_type'] ?? null;
        $transactionId = $payload['transaction_id'] ?? null;

        if (! $eventType || ! $transactionId) {
            Log::warning('Invalid Click to Pay webhook payload', ['payload' => $payload]);

            return;
        }

        // Find the payment intent by gateway_id
        $intent = PaymentIntent::where('gateway_id', $transactionId)->first();

        if (! $intent) {
            Log::warning('Payment intent not found for Click to Pay webhook', [
                'transaction_id' => $transactionId,
            ]);

            return;
        }

        match ($eventType) {
            'payment.succeeded' => $this->handlePaymentSucceeded($intent, $payload),
            'payment.failed' => $this->handlePaymentFailed($intent, $payload),
            'refund.completed' => $this->handleRefundCompleted($intent, $payload),
            'refund.failed' => $this->handleRefundFailed($intent, $payload),
            default => Log::info('Unhandled Click to Pay webhook event', [
                'event_type' => $eventType,
                'transaction_id' => $transactionId,
            ]),
        };
    }

    /**
     * Handle payment succeeded webhook event.
     */
    private function handlePaymentSucceeded(PaymentIntent $intent, array $payload): void
    {
        $intent->update([
            'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => now(),
            'metadata' => array_merge($intent->metadata ?? [], [
                'webhook_received_at' => now()->toIso8601String(),
                'transaction_details' => $payload,
            ]),
        ]);

        Log::info('Click to Pay payment succeeded via webhook', [
            'intent_id' => $intent->id,
            'booking_id' => $intent->booking_id,
        ]);
    }

    /**
     * Handle payment failed webhook event.
     */
    private function handlePaymentFailed(PaymentIntent $intent, array $payload): void
    {
        $intent->update([
            'status' => PaymentStatus::FAILED,
            'metadata' => array_merge($intent->metadata ?? [], [
                'webhook_received_at' => now()->toIso8601String(),
                'failure_reason' => $payload['reason'] ?? 'Unknown',
                'failure_details' => $payload,
            ]),
        ]);

        Log::error('Click to Pay payment failed via webhook', [
            'intent_id' => $intent->id,
            'booking_id' => $intent->booking_id,
            'reason' => $payload['reason'] ?? 'Unknown',
        ]);
    }

    /**
     * Handle refund completed webhook event.
     */
    private function handleRefundCompleted(PaymentIntent $intent, array $payload): void
    {
        $refundAmount = $payload['refund_amount'] ?? 0;
        $totalAmount = (int) ($intent->amount * 100);

        $intent->update([
            'status' => $refundAmount >= $totalAmount
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED,
            'metadata' => array_merge($intent->metadata ?? [], [
                'refund_completed_at' => now()->toIso8601String(),
                'refund_amount' => $refundAmount / 100,
                'refund_details' => $payload,
            ]),
        ]);

        Log::info('Click to Pay refund completed via webhook', [
            'intent_id' => $intent->id,
            'refund_amount' => $refundAmount / 100,
        ]);
    }

    /**
     * Handle refund failed webhook event.
     */
    private function handleRefundFailed(PaymentIntent $intent, array $payload): void
    {
        $intent->update([
            'metadata' => array_merge($intent->metadata ?? [], [
                'refund_failed_at' => now()->toIso8601String(),
                'refund_failure_reason' => $payload['reason'] ?? 'Unknown',
                'refund_failure_details' => $payload,
            ]),
        ]);

        Log::error('Click to Pay refund failed via webhook', [
            'intent_id' => $intent->id,
            'reason' => $payload['reason'] ?? 'Unknown',
        ]);
    }
}
