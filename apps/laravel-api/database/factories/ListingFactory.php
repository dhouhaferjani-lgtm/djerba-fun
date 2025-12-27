<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\Listing;
use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(3);

        return [
            'vendor_id' => User::factory()->create(['role' => 'vendor'])->id,
            'location_id' => Location::factory()->create()->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
            'title' => [
                'en' => $title,
                'fr' => $title,
            ],
            'slug' => Str::slug($title) . '-' . $this->faker->unique()->randomNumber(5),
            'summary' => [
                'en' => $this->faker->sentence(),
                'fr' => $this->faker->sentence(),
            ],
            'description' => [
                'en' => $this->faker->paragraph(),
                'fr' => $this->faker->paragraph(),
            ],
            'highlights' => [],
            'included' => [],
            'not_included' => [],
            'requirements' => [],
            'meeting_point' => [],
            'cancellation_policy' => [],
            'pricing' => [
                'currency' => 'EUR',
                'basePrice' => $this->faker->numberBetween(50, 200),
            ],
            'min_group_size' => 1,
            'max_group_size' => 20,
            'duration' => 120, // 2 hours in minutes
            'difficulty' => DifficultyLevel::MODERATE,
            'published_at' => now(),
        ];
    }

    /**
     * Create a listing with dual-currency pricing.
     */
    public function dualPriced(): static
    {
        return $this->state(fn (array $attributes) => [
            'pricing' => [
                'currency' => 'TND',
                'tnd_price' => 150,
                'eur_price' => 50,
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => ['en' => 'Adult', 'fr' => 'Adulte'],
                        'tnd_price' => 150,
                        'eur_price' => 50,
                        'minAge' => 18,
                    ],
                    [
                        'key' => 'child',
                        'label' => ['en' => 'Child', 'fr' => 'Enfant'],
                        'tnd_price' => 75,
                        'eur_price' => 25,
                        'minAge' => 4,
                        'maxAge' => 17,
                    ],
                    [
                        'key' => 'infant',
                        'label' => ['en' => 'Infant', 'fr' => 'Bébé'],
                        'tnd_price' => 0,
                        'eur_price' => 0,
                        'minAge' => 0,
                        'maxAge' => 3,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Create a published listing.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::PUBLISHED,
            'published_at' => now(),
        ]);
    }

    /**
     * Create a draft listing.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ListingStatus::DRAFT,
            'published_at' => null,
        ]);
    }
}
