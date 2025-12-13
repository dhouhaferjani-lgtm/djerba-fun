<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;

interface PaymentGateway
{
    /**
     * Create a payment intent for the booking.
     */
    public function createIntent(Booking $booking, array $options = []): PaymentIntent;

    /**
     * Process payment for the intent.
     */
    public function processPayment(PaymentIntent $intent, array $data): PaymentIntent;

    /**
     * Refund a payment intent.
     */
    public function refund(PaymentIntent $intent, ?int $amount = null): PaymentIntent;

    /**
     * Get the current status of a payment intent.
     */
    public function getStatus(PaymentIntent $intent): PaymentStatus;
}
