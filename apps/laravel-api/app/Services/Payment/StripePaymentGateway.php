<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Stripe Payment Gateway
 *
 * Integration with Stripe for card payments.
 * This is a stub implementation - full Stripe integration to be implemented.
 *
 * @see https://stripe.com/docs/api
 */
class StripePaymentGateway implements PaymentGateway
{
    /**
     * Create a payment intent for the booking.
     *
     * @param  Booking  $booking  The booking to create a payment for
     * @param  array  $options  Additional options
     * @return PaymentIntent The created payment intent
     *
     * @throws \Exception If Stripe API call fails
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent
    {
        $config = config('payment.gateways.stripe');

        if (! $config['enabled']) {
            throw new \Exception('Stripe gateway is not enabled');
        }

        if (empty($config['secret_key'])) {
            throw new \Exception('Stripe secret key is not configured');
        }

        // TODO: Implement actual Stripe API integration
        // For now, create a pending payment intent

        try {
            $paymentIntent = PaymentIntent::create([
                'booking_id' => $booking->id,
                'amount' => $booking->total_amount,
                'currency' => $booking->currency ?? 'USD',
                'payment_method' => $options['payment_method'] ?? 'card',
                'status' => PaymentStatus::PENDING,
                'gateway' => 'stripe',
                'gateway_id' => 'stripe_' . Str::random(20),
                'metadata' => array_merge($options['metadata'] ?? [], [
                    'created_at' => now()->toIso8601String(),
                    'stub_mode' => true,
                ]),
            ]);

            // TODO: Call Stripe API to create payment intent
            // $stripe = new \Stripe\StripeClient($config['secret_key']);
            // $stripeIntent = $stripe->paymentIntents->create([
            //     'amount' => (int) ($booking->total_amount * 100), // Amount in cents
            //     'currency' => strtolower($booking->currency ?? 'usd'),
            //     'metadata' => [
            //         'booking_id' => $booking->id,
            //         'booking_number' => $booking->booking_number,
            //     ],
            // ]);
            //
            // $paymentIntent->update([
            //     'gateway_id' => $stripeIntent->id,
            //     'metadata' => array_merge($paymentIntent->metadata ?? [], [
            //         'client_secret' => $stripeIntent->client_secret,
            //     ]),
            // ]);

            Log::info('Stripe payment intent created (stub mode)', [
                'payment_intent_id' => $paymentIntent->id,
                'booking_id' => $booking->id,
            ]);

            return $paymentIntent;
        } catch (\Exception $e) {
            Log::error('Stripe payment intent creation failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);

            throw new \Exception('Failed to create Stripe payment: ' . $e->getMessage());
        }
    }

    /**
     * Process payment for the intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to process
     * @param  array  $data  Payment data from Stripe
     * @return PaymentIntent The updated payment intent
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent
    {
        // TODO: Implement Stripe payment confirmation
        // This would typically be called from a webhook handler

        $intent->update([
            'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => now(),
            'metadata' => array_merge($intent->metadata ?? [], [
                'stub_mode' => true,
                'processed_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Stripe payment processed (stub mode)', [
            'payment_intent_id' => $intent->id,
        ]);

        return $intent->fresh();
    }

    /**
     * Refund a payment intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to refund
     * @param  int|null  $amount  Amount to refund in cents (null = full refund)
     * @return PaymentIntent The updated payment intent
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        $refundAmount = $amount ?? (int) ($intent->amount * 100);
        $totalAmount = (int) ($intent->amount * 100);

        // TODO: Call Stripe refund API
        // $config = config('payment.gateways.stripe');
        // $stripe = new \Stripe\StripeClient($config['secret_key']);
        // $refund = $stripe->refunds->create([
        //     'payment_intent' => $intent->gateway_id,
        //     'amount' => $refundAmount,
        // ]);

        $intent->update([
            'status' => $refundAmount === $totalAmount
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED,
            'metadata' => array_merge($intent->metadata ?? [], [
                'stub_mode' => true,
                'refunded_at' => now()->toIso8601String(),
                'refund_amount' => $refundAmount / 100,
            ]),
        ]);

        Log::info('Stripe refund processed (stub mode)', [
            'payment_intent_id' => $intent->id,
            'refund_amount' => $refundAmount / 100,
        ]);

        return $intent->fresh();
    }

    /**
     * Get the current status of a payment intent.
     *
     * @param  PaymentIntent  $intent  The payment intent to check
     * @return PaymentStatus The current payment status
     */
    public function getStatus(PaymentIntent $intent): PaymentStatus
    {
        // TODO: Optionally query Stripe API for real-time status
        // $config = config('payment.gateways.stripe');
        // $stripe = new \Stripe\StripeClient($config['secret_key']);
        // $stripeIntent = $stripe->paymentIntents->retrieve($intent->gateway_id);
        //
        // return match($stripeIntent->status) {
        //     'requires_payment_method' => PaymentStatus::PENDING,
        //     'requires_confirmation' => PaymentStatus::PENDING,
        //     'requires_action' => PaymentStatus::PENDING,
        //     'processing' => PaymentStatus::PROCESSING,
        //     'succeeded' => PaymentStatus::SUCCEEDED,
        //     'canceled' => PaymentStatus::CANCELLED,
        //     default => $intent->status,
        // };

        return $intent->status;
    }
}
