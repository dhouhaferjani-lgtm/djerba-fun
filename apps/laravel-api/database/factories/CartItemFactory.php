<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AvailabilitySlot;
use App\Models\BookingHold;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CartItem>
 */
class CartItemFactory extends Factory
{
    protected $model = CartItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'listing_id' => Listing::factory(),
            'availability_slot_id' => AvailabilitySlot::factory(),
            'hold_id' => null,
            'quantity' => fake()->numberBetween(1, 4),
            'person_type_breakdown' => [
                'adults' => fake()->numberBetween(1, 3),
                'children' => fake()->numberBetween(0, 2),
            ],
            'extras' => [],
            'unit_price' => fake()->randomFloat(2, 50, 200),
            'total_price' => fake()->randomFloat(2, 50, 500),
            'currency' => 'CAD',
        ];
    }

    /**
     * Indicate that the cart item has a hold.
     */
    public function withHold(): static
    {
        return $this->state(fn (array $attributes) => [
            'hold_id' => BookingHold::factory(),
        ]);
    }

    /**
     * Indicate that the cart item has extras.
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
}
