<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Partner;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Partner>
 */
class PartnerFactory extends Factory
{
    protected $model = Partner::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
            'permissions' => ['listings:read', 'bookings:read', 'bookings:create'],
            'webhook_url' => fake()->url(),
            'webhook_secret' => Str::random(32),
            'rate_limit' => 1000,
            'metadata' => [
                'integration_type' => 'API',
                'contact_person' => fake()->name(),
            ],
        ];
    }

    /**
     * Indicate that the partner is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate full permissions.
     */
    public function fullPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => ['*'],
        ]);
    }

    /**
     * Indicate read-only permissions.
     */
    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => ['listings:read', 'bookings:read'],
        ]);
    }
}
