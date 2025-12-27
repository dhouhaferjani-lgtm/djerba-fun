<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AvailabilitySlot;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailabilitySlot>
 */
class AvailabilitySlotFactory extends Factory
{
    protected $model = AvailabilitySlot::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = now()->addDays($this->faker->numberBetween(1, 30));

        $capacity = $this->faker->numberBetween(5, 20);

        return [
            'listing_id' => Listing::factory()->create()->id,
            'date' => $startTime->toDateString(),
            'start_time' => $startTime,
            'end_time' => $startTime->copy()->addHours(2),
            'capacity' => $capacity,
            'remaining_capacity' => $capacity,
            'base_price' => $this->faker->numberBetween(50, 200),
            'currency' => 'EUR',
            'status' => 'available',
        ];
    }

    /**
     * Create a slot with TND currency.
     */
    public function tnd(): static
    {
        return $this->state(fn (array $attributes) => [
            'base_price' => 150,
            'currency' => 'TND',
        ]);
    }

    /**
     * Create a slot with EUR currency.
     */
    public function eur(): static
    {
        return $this->state(fn (array $attributes) => [
            'base_price' => 50,
            'currency' => 'EUR',
        ]);
    }

    /**
     * Create a slot starting tomorrow.
     */
    public function tomorrow(): static
    {
        $tomorrow = now()->addDay();

        return $this->state(fn (array $attributes) => [
            'date' => $tomorrow->toDateString(),
            'start_time' => $tomorrow->setTime(10, 0),
            'end_time' => $tomorrow->setTime(12, 0),
        ]);
    }

    /**
     * Create a fully booked slot.
     */
    public function fullyBooked(): static
    {
        return $this->state(fn (array $attributes) => [
            'capacity' => 10,
            'status' => 'full',
        ]);
    }
}
