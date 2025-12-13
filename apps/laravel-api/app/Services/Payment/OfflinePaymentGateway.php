<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Support\Str;

class OfflinePaymentGateway implements PaymentGateway
{
    /**
     * Create a payment intent for the booking.
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent
    {
        return PaymentIntent::create([
            'booking_id' => $booking->id,
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'payment_method' => $options['payment_method'] ?? 'cash_on_arrival',
            'status' => PaymentStatus::PENDING,
            'gateway' => 'offline',
            'gateway_id' => 'offline_' . Str::random(16),
            'metadata' => array_merge($options['metadata'] ?? [], [
                'created_at' => now()->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Process payment for the intent (offline payments require manual confirmation).
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent
    {
        // Offline payments remain pending until manually confirmed
        $intent->update([
            'status' => PaymentStatus::PENDING,
            'metadata' => array_merge($intent->metadata ?? [], [
                'payment_instructions' => $data['instructions'] ?? null,
                'expected_payment_date' => $data['expected_date'] ?? null,
            ]),
        ]);

        return $intent->fresh();
    }

    /**
     * Manually confirm an offline payment.
     */
    public function confirmPayment(PaymentIntent $intent, array $data = []): PaymentIntent
    {
        $intent->update([
            'status' => PaymentStatus::SUCCEEDED,
            'paid_at' => now(),
            'metadata' => array_merge($intent->metadata ?? [], [
                'confirmed_at' => now()->toIso8601String(),
                'confirmation_reference' => $data['reference'] ?? null,
                'confirmed_by' => $data['confirmed_by'] ?? null,
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
                'refund_method' => 'offline',
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
