<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => [
                'en' => $this->faker->city(),
                'fr' => $this->faker->city(),
            ],
            'slug' => $this->faker->unique()->slug(),
            'city' => $this->faker->city(),
            'region' => $this->faker->state(),
            'country' => $this->faker->randomElement(['TN', 'FR', 'MA']),
            'timezone' => 'Africa/Tunis',
            'latitude' => $this->faker->latitude(30, 50),
            'longitude' => $this->faker->longitude(-10, 15),
            'description' => [
                'en' => $this->faker->paragraph(),
                'fr' => $this->faker->paragraph(),
            ],
        ];
    }

    /**
     * Create a Tunisia location.
     */
    public function tunisia(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => ['en' => 'Tunis', 'fr' => 'Tunis'],
            'city' => 'Tunis',
            'region' => 'Tunis',
            'country' => 'TN',
            'timezone' => 'Africa/Tunis',
            'latitude' => 36.8065,
            'longitude' => 10.1815,
        ]);
    }

    /**
     * Create a France location.
     */
    public function france(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => ['en' => 'Paris', 'fr' => 'Paris'],
            'city' => 'Paris',
            'region' => 'Île-de-France',
            'country' => 'FR',
            'timezone' => 'Europe/Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ]);
    }
}
