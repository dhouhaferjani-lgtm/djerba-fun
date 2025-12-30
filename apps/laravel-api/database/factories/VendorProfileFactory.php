<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\KycStatus;
use App\Models\User;
use App\Models\VendorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\VendorProfile>
 */
class VendorProfileFactory extends Factory
{
    protected $model = VendorProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state([
                'role' => 'vendor',
            ]),
            'business_name' => fake()->company(),
            'business_type' => fake()->randomElement(['individual', 'company', 'partnership']),
            'business_registration_number' => fake()->numerify('BN########'),
            'tax_id' => fake()->numerify('TAX#########'),
            'kyc_status' => KycStatus::PENDING,
            'kyc_submitted_at' => null,
            'kyc_verified_at' => null,
            'kyc_notes' => null,
            'bank_account_name' => fake()->name(),
            'bank_account_number' => fake()->bankAccountNumber(),
            'bank_routing_number' => fake()->numerify('#########'),
            'payout_schedule' => 'monthly',
            'commission_rate' => fake()->randomFloat(2, 10, 25),
            'auto_payout_enabled' => true,
            'minimum_payout_amount' => 100.00,
        ];
    }

    /**
     * Indicate that KYC is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => KycStatus::VERIFIED,
            'kyc_submitted_at' => now()->subWeek(),
            'kyc_verified_at' => now(),
        ]);
    }

    /**
     * Indicate that KYC is rejected.
     */
    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => KycStatus::REJECTED,
            'kyc_submitted_at' => now()->subWeek(),
            'kyc_notes' => 'Invalid documentation provided',
        ]);
    }

    /**
     * Indicate that KYC is under review.
     */
    public function underReview(): static
    {
        return $this->state(fn (array $attributes) => [
            'kyc_status' => KycStatus::UNDER_REVIEW,
            'kyc_submitted_at' => now()->subDays(3),
        ]);
    }
}
