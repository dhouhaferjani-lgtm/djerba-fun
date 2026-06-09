<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ActivityType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityType>
 */
class ActivityTypeFactory extends Factory
{
    protected $model = ActivityType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $word = $this->faker->unique()->word();

        return [
            'name' => [
                'en' => ucfirst($word),
                'fr' => ucfirst($word),
            ],
            'slug' => $word . '-' . $this->faker->unique()->randomNumber(5),
            'description' => [
                'en' => $this->faker->sentence(),
                'fr' => $this->faker->sentence(),
            ],
            'icon' => 'heroicon-o-tag',
            'color' => $this->faker->hexColor(),
            'display_order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
