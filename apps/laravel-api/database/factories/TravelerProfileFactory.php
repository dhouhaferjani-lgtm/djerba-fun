<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\TravelerProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TravelerProfile>
 */
class TravelerProfileFactory extends Factory
{
    protected $model = TravelerProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->create(['role' => 'traveler'])->id,
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->phoneNumber(),
            'default_currency' => $this->faker->randomElement(['EUR', 'TND']),
            'preferred_locale' => $this->faker->randomElement(['en', 'fr']),
            'documents' => [],
        ];
    }

    /**
     * Create a profile with Tunisia billing country (TND currency).
     */
    public function tunisiaBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_currency' => 'TND',
            'preferred_locale' => 'fr',
        ]);
    }

    /**
     * Create a profile with France billing country (EUR currency).
     */
    public function franceBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_currency' => 'EUR',
            'preferred_locale' => 'fr',
        ]);
    }
}
