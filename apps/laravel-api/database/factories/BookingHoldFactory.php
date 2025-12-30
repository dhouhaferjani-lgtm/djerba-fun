<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\HoldStatus;
use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingHold>
 */
class BookingHoldFactory extends Factory
{
    protected $model = BookingHold::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'listing_id' => Listing::factory(),
            'slot_id' => AvailabilitySlot::factory(),
            'user_id' => null,
            'session_id' => Str::uuid()->toString(),
            'cart_id' => null,
            'quantity' => fake()->numberBetween(1, 4),
            'person_type_breakdown' => [
                'adults' => fake()->numberBetween(1, 3),
                'children' => fake()->numberBetween(0, 2),
            ],
            'currency' => 'CAD',
            'price_snapshot' => fake()->randomFloat(2, 50, 200),
            'pricing_country_code' => 'CA',
            'pricing_source' => 'api',
            'expires_at' => now()->addMinutes(BookingHold::HOLD_DURATION_MINUTES),
            'status' => HoldStatus::ACTIVE,
        ];
    }

    /**
     * Indicate that the hold belongs to an authenticated user.
     */
    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    /**
     * Indicate that the hold belongs to a cart.
     */
    public function forCart(?Cart $cart = null): static
    {
        return $this->state(fn (array $attributes) => [
            'cart_id' => $cart?->id ?? Cart::factory(),
        ]);
    }

    /**
     * Indicate that the hold has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinutes(5),
            'status' => HoldStatus::EXPIRED,
        ]);
    }

    /**
     * Indicate that the hold is completed (converted to booking).
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HoldStatus::COMPLETED,
        ]);
    }

    /**
     * Indicate that the hold is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HoldStatus::CANCELLED,
        ]);
    }
}
