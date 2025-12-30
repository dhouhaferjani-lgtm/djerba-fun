<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\IncomePricingConfig;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\IncomePricingConfig>
 */
class IncomePricingConfigFactory extends Factory
{
    protected $model = IncomePricingConfig::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'from_currency' => 'TND',
            'to_currency' => 'EUR',
            'ratio' => 0.1286,
            'tolerance_percent' => 20,
            'is_active' => true,
            'effective_from' => now()->subDay(),
            'notes' => 'Default income parity configuration',
        ];
    }

    /**
     * Indicate that the config is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Set a specific parity ratio.
     */
    public function withRatio(float $ratio): static
    {
        return $this->state(fn (array $attributes) => [
            'ratio' => $ratio,
        ]);
    }

    /**
     * Set a specific tolerance percentage.
     */
    public function withTolerance(float $tolerance): static
    {
        return $this->state(fn (array $attributes) => [
            'tolerance_percent' => $tolerance,
        ]);
    }
}
