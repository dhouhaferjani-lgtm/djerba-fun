<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Booking;
use App\Models\BookingParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BookingParticipant>
 */
class BookingParticipantFactory extends Factory
{
    protected $model = BookingParticipant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->email(),
            'phone' => fake()->phoneNumber(),
            'person_type' => 'adult',
            'special_requests' => null,
            'checked_in' => false,
            'checked_in_at' => null,
        ];
    }

    /**
     * Indicate participant is incomplete (missing names).
     */
    public function incomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'first_name' => null,
            'last_name' => null,
        ]);
    }

    /**
     * Indicate participant has been checked in.
     */
    public function checkedIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'checked_in' => true,
            'checked_in_at' => now(),
        ]);
    }

    /**
     * Create a child participant.
     */
    public function child(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 'child',
        ]);
    }

    /**
     * Create an infant participant.
     */
    public function infant(): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type' => 'infant',
        ]);
    }
}
