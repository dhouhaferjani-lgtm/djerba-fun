<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGateway
{
    /**
     * Create a payment intent for the booking.
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent
    {
        return PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => $booking->getPayableAmount(),
            'currency' => $booking->currency,
            'payment_method' => $options['payment_method'] ?? 'card',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'mock',
            'gateway_id' => 'mock_' . Str::random(20),
            'metadata' => $options['metadata'] ?? null,
        ]);
    }

    /**
     * Process payment for the intent.
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent
    {
        // Simulate processing delay
        sleep(2);

        // Mock payment always succeeds
        $intent->update([
            'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => now(),
            'metadata' => array_merge($intent->metadata ?? [], [
                'processed_at' => now()->toIso8601String(),
                'mock_transaction_id' => 'txn_' . Str::random(24),
            ]),
        ]);

        return $intent->fresh();
    }

    /**
     * Refund a payment intent.
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent
    {
        $refundAmount = $amount ?? (int) ($intent->amount * 100);

        $intent->update([
            'status' => $refundAmount === (int) ($intent->amount * 100)
                ? PaymentStatus::REFUNDED
                : PaymentStatus::PARTIALLY_REFUNDED,
            'metadata' => array_merge($intent->metadata ?? [], [
                'refunded_at' => now()->toIso8601String(),
                'refund_amount' => $refundAmount / 100,
                'mock_refund_id' => 'rfnd_' . Str::random(24),
            ]),
        ]);

        return $intent->fresh();
    }

    /**
     * Get the current status of a payment intent.
     */
    public function getStatus(PaymentIntent $intent): PaymentStatus
    {
        return $intent->status;
    }
}
