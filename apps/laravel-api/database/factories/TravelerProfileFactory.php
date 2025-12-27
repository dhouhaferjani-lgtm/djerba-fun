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
            'nationality' => $this->faker->countryCode(),
            'date_of_birth' => $this->faker->date(),
            'phone' => $this->faker->phoneNumber(),
            'emergency_contact' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'relationship' => $this->faker->randomElement(['spouse', 'parent', 'sibling', 'friend']),
            ],
            'preferences' => [],
        ];
    }

    /**
     * Create a profile with Tunisia billing country.
     */
    public function tunisiaBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => [
                'billing_country' => 'TN',
            ],
        ]);
    }

    /**
     * Create a profile with France billing country.
     */
    public function franceBilling(): static
    {
        return $this->state(fn (array $attributes) => [
            'preferences' => [
                'billing_country' => 'FR',
            ],
        ]);
    }
}
