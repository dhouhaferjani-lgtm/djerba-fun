<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\AvailabilitySlot;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_number' => 'BK-' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'session_id' => null,
            'magic_token' => null,
            'magic_token_expires_at' => null,
            'listing_id' => Listing::factory(),
            'availability_slot_id' => AvailabilitySlot::factory(),
            'coupon_id' => null,
            'cart_payment_id' => null,
            'partner_id' => null,
            'quantity' => fake()->numberBetween(1, 4),
            'person_type_breakdown' => [
                'adults' => fake()->numberBetween(1, 3),
                'children' => fake()->numberBetween(0, 2),
            ],
            'total_amount' => fake()->randomFloat(2, 50, 500),
            'discount_amount' => 0,
            'currency' => 'CAD',
            'billing_country_code' => 'CA',
            'billing_city' => fake()->city(),
            'billing_postal_code' => fake()->postcode(),
            'billing_address_line1' => fake()->streetAddress(),
            'billing_address_line2' => null,
            'pricing_snapshot' => [
                'base_price' => fake()->randomFloat(2, 50, 500),
                'extras' => [],
                'discount' => 0,
            ],
            'pricing_disclosed' => true,
            'status' => BookingStatus::PENDING_PAYMENT,
            'traveler_info' => [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->email(),
                'phone' => fake()->phoneNumber(),
            ],
            'travelers' => [],
            'extras' => [],
            'partner_metadata' => null,
            'billing_contact' => [
                'email' => fake()->email(),
                'phone' => fake()->phoneNumber(),
            ],
            'confirmed_at' => null,
            'cancelled_at' => null,
            'cancellation_reason' => null,
            'traveler_details_status' => 'pending',
            'traveler_details_completed_at' => null,
            'linked_at' => null,
            'linked_method' => null,
        ];
    }

    /**
     * Indicate that the booking is confirmed.
     */
    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::CONFIRMED,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Indicate that the booking is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => BookingStatus::CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => 'Customer requested cancellation',
        ]);
    }

    /**
     * Indicate that the booking has a magic token for guest access.
     */
    public function withMagicToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'magic_token' => Str::random(64),
            'magic_token_expires_at' => now()->addDays(30),
        ]);
    }

    /**
     * Indicate that the booking is linked to a user account.
     */
    public function linked(): static
    {
        return $this->state(fn (array $attributes) => [
            'linked_at' => now(),
            'linked_method' => 'magic_link',
        ]);
    }

    /**
     * Indicate that traveler details are complete.
     */
    public function withTravelerDetails(): static
    {
        return $this->state(fn (array $attributes) => [
            'travelers' => [
                [
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                    'email' => fake()->email(),
                    'phone' => fake()->phoneNumber(),
                ],
            ],
            'traveler_details_status' => 'complete',
            'traveler_details_completed_at' => now(),
        ]);
    }

    /**
     * Indicate that the booking has extras.
     */
    public function withExtras(): static
    {
        return $this->state(fn (array $attributes) => [
            'extras' => [
                [
                    'extra_id' => fake()->uuid(),
                    'name' => 'Equipment Rental',
                    'quantity' => 2,
                    'price_per_unit' => 25.00,
                ],
            ],
        ]);
    }

    /**
     * Indicate that the booking has a coupon applied.
     */
    public function withCoupon(): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_amount' => fake()->randomFloat(2, 10, 50),
        ]);
    }
}
