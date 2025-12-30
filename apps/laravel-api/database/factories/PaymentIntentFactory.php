<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\PaymentIntent;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PaymentIntent>
 */
class PaymentIntentFactory extends Factory
{
    protected $model = PaymentIntent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'gateway' => 'mock',
            'gateway_payment_id' => 'pi_' . Str::random(24),
            'amount' => fake()->randomFloat(2, 50, 500),
            'currency' => 'CAD',
            'status' => PaymentStatus::PENDING,
            'payment_method' => 'card',
            'metadata' => [
                'card_last4' => '4242',
                'card_brand' => 'visa',
            ],
            'error_message' => null,
            'processed_at' => null,
        ];
    }

    /**
     * Indicate that the payment is succeeded.
     */
    public function succeeded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::SUCCEEDED,
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::FAILED,
            'error_message' => 'Card declined',
            'processed_at' => now(),
        ]);
    }

    /**
     * Indicate that the payment is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::PROCESSING,
        ]);
    }

    /**
     * Indicate that the payment is refunded.
     */
    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PaymentStatus::REFUNDED,
            'processed_at' => now(),
        ]);
    }
}
