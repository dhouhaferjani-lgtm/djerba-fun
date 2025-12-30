<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payout>
 */
class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'vendor_profile_id' => VendorProfile::factory(),
            'payout_number' => 'PO-' . strtoupper(Str::random(8)),
            'amount' => fake()->randomFloat(2, 100, 5000),
            'currency' => 'CAD',
            'status' => PayoutStatus::PENDING,
            'period_start' => now()->subMonth()->startOfMonth(),
            'period_end' => now()->subMonth()->endOfMonth(),
            'paid_at' => null,
            'bank_reference' => null,
            'notes' => null,
        ];
    }

    /**
     * Indicate that the payout is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::COMPLETED,
            'paid_at' => now(),
            'bank_reference' => 'BR-' . Str::random(12),
        ]);
    }

    /**
     * Indicate that the payout is processing.
     */
    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::PROCESSING,
        ]);
    }

    /**
     * Indicate that the payout has failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PayoutStatus::FAILED,
            'notes' => 'Payment processing failed - invalid bank details',
        ]);
    }
}
