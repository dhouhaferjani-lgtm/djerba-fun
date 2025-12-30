<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(Str::random(8)),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'discount_type' => 'percentage',
            'discount_value' => fake()->randomFloat(2, 5, 50),
            'minimum_order' => null,
            'maximum_discount' => null,
            'valid_from' => now(),
            'valid_until' => now()->addMonths(3),
            'usage_limit' => null,
            'usage_count' => 0,
            'is_active' => true,
            'listing_ids' => null,
            'user_ids' => null,
        ];
    }

    /**
     * Indicate that the coupon is a fixed amount discount.
     */
    public function fixedAmount(float $amount = 10.00): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'fixed_amount',
            'discount_value' => $amount,
        ]);
    }

    /**
     * Indicate that the coupon is a percentage discount.
     */
    public function percentage(float $percentage = 10.00): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_type' => 'percentage',
            'discount_value' => $percentage,
        ]);
    }

    /**
     * Indicate that the coupon has a maximum discount amount.
     */
    public function withMaxDiscount(float $maxDiscount): static
    {
        return $this->state(fn (array $attributes) => [
            'maximum_discount' => $maxDiscount,
        ]);
    }

    /**
     * Indicate that the coupon has a minimum purchase requirement.
     */
    public function withMinPurchase(float $minPurchase): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_order' => $minPurchase,
        ]);
    }

    /**
     * Indicate that the coupon has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_until' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the coupon is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the coupon has reached max uses.
     */
    public function maxedOut(): static
    {
        return $this->state(function (array $attributes) {
            $maxUses = fake()->numberBetween(10, 100);
            return [
                'usage_limit' => $maxUses,
                'usage_count' => $maxUses,
            ];
        });
    }

    /**
     * Indicate that the coupon is for specific listings.
     */
    public function forListings(array $listingIds): static
    {
        return $this->state(fn (array $attributes) => [
            'listing_ids' => $listingIds,
        ]);
    }
}
