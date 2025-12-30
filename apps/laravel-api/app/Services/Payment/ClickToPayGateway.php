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

/**
 * Click to Pay Gateway for Tunisian Payment Processing
 *
 * This gateway integrates with Tunisia's Click to Pay payment processor.
 * Implementation stubs are provided for future API integration.
 */
class ClickToPayGateway implements PaymentGateway
{
    /**
     * Create a payment intent for the booking.
     *
     * @param  Booking  $booking  The booking to create a payment for
     * @param  array  $options  Additional options (payment_method, metadata, etc.)
     * @return PaymentIntent The created payment intent
     *
     * @throws \Exception If configuration is invalid or API call fails
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent
    {
        $config = config('payment.gateways.clicktopay');

        // Validate configuration
        if (! $config['enabled']) {
            throw new \Exception('Click to Pay gateway is not enabled');
        }

        if (empty($config['merchant_id']) || empty($config['api_key'])) {
            throw new \Exception('Click to Pay gateway is not properly configured');
        }

        // TODO: Implement actual Click to Pay API integration
        // For now, create a pending payment intent with gateway reference

        try {
            // Convert amount to smallest currency unit (millimes for TND)
            $amountInMillimes = (int) ($booking->total_amount * 1000);

            $paymentIntent = PaymentIntent::create([
                'booking_id' => $booking->id,
                'amount' => $booking->total_amount,
                'currency' => $booking->currency ?? 'TND',
                'payment_method' => $options['payment_method'] ?? 'card',
                'status' => PaymentStatus::PENDING,
                'gateway' => 'clicktopay',
                'gateway_id' => $this->generateGatewayId(),
                'metadata' => array_merge($options['metadata'] ?? [], [
                    'merchant_id' => $config['merchant_id'],
                    'test_mode' => $config['test_mode'] ?? false,
                    'created_at' => now()->toIso8601String(),
                    'amount_in_millimes' => $amountInMillimes,
                ]),
            ]);

            // TODO: Call Click to Pay API to create transaction
            // $response = $this->callClickToPayAPI('createTransaction', [
            //     'merchant_id' => $config['merchant_id'],
            //     'amount' => $amountInMillimes,
            //     'currency' => $booking->currency ?? 'TND',
            //     'order_id' => $paymentIntent->id,
            //     'return_url' => route('payment.callback', ['gateway' => 'clicktopay']),
            //     'cancel_url' => route('payment.cancelled', ['gateway' => 'clicktopay']),
            // ]);
            //
            // $paymentIntent->update([
            //     'gateway_id' => $response['transaction_id'],
            //     'metadata' => array_merge($paymentIntent->metadata ?? [], [
            //         'payment_url' => $response['payment_url'],
            //     ]),
            // ]);

            Log::info('Click to Pay payment intent created', [
                'payment_intent_id' => $paymentIntent->id,
                'booking_id' => $booking->id,
                'amount' => $booking->total_amount,
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Click to Pay payment intent creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to create Click to Pay payment: ' . $e->getMessage());
        }
    }

    /**
     * Process payment for the intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to process
     * @param  array  $data  Payment data from callback/webhook
     * @return PaymentIntent The updated payment intent
     *
     * @throws \Exception If payment processing fails
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent
    {
        $config = config('payment.gateways.clicktopay');

        try {
            // TODO: Verify payment with Click to Pay API
            // $response = $this->callClickToPayAPI('verifyTransaction', [
            //     'merchant_id' => $config['merchant_id'],
            //     'transaction_id' => $intent->gateway_id,
            //     'signature' => $data['signature'] ?? null,
            // ]);
            //
            // if ($response['status'] === 'success') {
            //     $intent->update([
            //         'status' => PaymentStatus::SUCCEEDED,
            //         'paid_at' => now(),
            //         'metadata' => array_merge($intent->metadata ?? [], [
            //             'transaction_id' => $response['transaction_id'],
            //             'payment_method' => $response['payment_method'] ?? 'card',
            //             'processed_at' => now()->toIso8601String(),
            //         ]),
            //     ]);
            // } else {
            //     $intent->update([
            //         'status' => PaymentStatus::FAILED,
            //         'metadata' => array_merge($intent->metadata ?? [], [
            //             'error' => $response['error'] ?? 'Payment failed',
            //             'failed_at' => now()->toIso8601String(),
            //         ]),
            //     ]);
            // }

            // STUB: For development, mark as succeeded
            // Remove this when actual API integration is implemented
            $intent->update([
                'status' => PaymentStatus::SUCCEEDED,
                'paid_at' => now(),
                'metadata' => array_merge($intent->metadata ?? [], [
                    'stub_mode' => true,
                    'processed_at' => now()->toIso8601String(),
                    'clicktopay_transaction_id' => 'ctp_' . Str::random(24),
                ]),
            ]);

            Log::info('Click to Pay payment processed', [
                'payment_intent_id' => $intent->id,
                'status' => $intent->status->value,
            ]);

            return $intent->fresh();
        } catch (\Exception $e) {
            Log::error('Click to Pay payment processing failed', [
                'payment_intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to process Click to Pay payment: ' . $e->getMessage());
        }
    }

    /**
     * Refund a payment intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to refund
     * @param  int|null  $amount  Amount to refund in smallest currency unit (null = full refund)
     * @return PaymentIntent The updated payment intent
     *
     * @throws \Exception If refund fails
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        $config = config('payment.gateways.clicktopay');

        // Calculate refund amount in millimes (1 TND = 1000 millimes)
        $refundAmountInMillimes = $amount ?? (int) ($intent->amount * 1000);
        $totalAmountInMillimes = (int) ($intent->amount * 1000);

        try {
            // TODO: Call Click to Pay refund API
            // $response = $this->callClickToPayAPI('refundTransaction', [
            //     'merchant_id' => $config['merchant_id'],
            //     'transaction_id' => $intent->gateway_id,
            //     'amount' => $refundAmountInMillimes,
            // ]);
            //
            // if ($response['status'] === 'success') {
            //     $intent->update([
            //         'status' => $refundAmountInMillimes === $totalAmountInMillimes
            //             ? PaymentStatus::REFUNDED
            //             : PaymentStatus::PARTIALLY_REFUNDED,
            //         'metadata' => array_merge($intent->metadata ?? [], [
            //             'refunded_at' => now()->toIso8601String(),
            //             'refund_amount_millimes' => $refundAmountInMillimes,
            //             'refund_amount' => $refundAmountInMillimes / 1000,
            //             'refund_transaction_id' => $response['refund_transaction_id'],
            //         ]),
            //     ]);
            // }

            // STUB: For development, mark as refunded
            // Remove this when actual API integration is implemented
            $intent->update([
                'status' => $refundAmountInMillimes === $totalAmountInMillimes
                    ? PaymentStatus::REFUNDED
                    : PaymentStatus::PARTIALLY_REFUNDED,
                'metadata' => array_merge($intent->metadata ?? [], [
                    'stub_mode' => true,
                    'refunded_at' => now()->toIso8601String(),
                    'refund_amount_millimes' => $refundAmountInMillimes,
                    'refund_amount' => $refundAmountInMillimes / 1000,
                    'clicktopay_refund_id' => 'rfnd_' . Str::random(24),
                ]),
            ]);

            Log::info('Click to Pay refund processed', [
                'payment_intent_id' => $intent->id,
                'refund_amount' => $refundAmountInMillimes / 1000,
                'status' => $intent->status->value,
            ]);

            return $intent->fresh();
        } catch (\Exception $e) {
            Log::error('Click to Pay refund failed', [
                'payment_intent_id' => $intent->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to process Click to Pay refund: ' . $e->getMessage());
        }
    }

    /**
     * Get the current status of a payment intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to check
     * @return PaymentStatus The current payment status
     */
    public function getStatus(PaymentIntent $intent): PaymentStatus
    {
        // TODO: Optionally call Click to Pay API to get real-time status
        // $response = $this->callClickToPayAPI('getTransactionStatus', [
        //     'merchant_id' => config('payment.gateways.clicktopay.merchant_id'),
        //     'transaction_id' => $intent->gateway_id,
        // ]);
        //
        // return match($response['status']) {
        //     'pending' => PaymentStatus::PENDING,
        //     'processing' => PaymentStatus::PROCESSING,
        //     'success' => PaymentStatus::SUCCEEDED,
        //     'failed' => PaymentStatus::FAILED,
        //     default => $intent->status,
        // };

        return $intent->status;
    }

    /**
     * Generate a unique gateway ID for tracking.
     *
     * @return string
     */
    protected function generateGatewayId(): string
    {
        return 'ctp_' . Str::random(20);
    }

    /**
     * Call Click to Pay API endpoint.
     *
     * TODO: Implement when actual API credentials and documentation are available.
     *
     * @param  string  $endpoint  The API endpoint to call
     * @param  array  $data  The data to send
     * @return array The API response
     *
     * @throws \Exception If API call fails
     */
    protected function callClickToPayAPI(string $endpoint, array $data): array
    {
        $config = config('payment.gateways.clicktopay');

        $baseUrl = $config['test_mode']
            ? 'https://test.clicktopay.tn/api'  // TODO: Replace with actual test URL
            : 'https://api.clicktopay.tn/api';  // TODO: Replace with actual production URL

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'X-Merchant-ID' => $config['merchant_id'],
                'Content-Type' => 'application/json',
            ])
                ->timeout(30)
                ->post("{$baseUrl}/{$endpoint}", $data);

            if ($response->failed()) {
                throw new \Exception('Click to Pay API request failed: ' . $response->body());
            }

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Click to Pay API call failed', [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
