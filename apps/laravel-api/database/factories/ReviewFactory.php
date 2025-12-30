<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'listing_id' => Listing::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(3, 5),
            'title' => fake()->sentence(),
            'comment' => fake()->paragraph(),
            'verified_purchase' => true,
            'is_published' => true,
            'helpful_count' => fake()->numberBetween(0, 50),
            'reported_count' => 0,
            'moderation_status' => 'approved',
            'moderation_notes' => null,
        ];
    }

    /**
     * Indicate that the review is not from a verified purchase.
     */
    public function notVerified(): static
    {
        return $this->state(fn (array $attributes) => [
            'verified_purchase' => false,
            'booking_id' => null,
        ]);
    }

    /**
     * Indicate that the review is pending moderation.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'moderation_status' => 'pending',
        ]);
    }

    /**
     * Indicate that the review is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'moderation_status' => 'rejected',
            'moderation_notes' => 'Inappropriate content',
        ]);
    }

    /**
     * Indicate that the review has a low rating.
     */
    public function lowRating(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => fake()->numberBetween(1, 2),
        ]);
    }

    /**
     * Indicate that the review has been reported.
     */
    public function reported(): static
    {
        return $this->state(fn (array $attributes) => [
            'reported_count' => fake()->numberBetween(1, 5),
            'moderation_status' => 'flagged',
        ]);
    }
}
