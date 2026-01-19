<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates default blog categories for the travel blog.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Travel Tips',
                'slug' => 'travel-tips',
                'description' => 'Practical travel advice and tips',
                'color' => '#0D642E',
                'sort_order' => 1,
            ],
            [
                'name' => 'Destinations',
                'slug' => 'destinations',
                'description' => 'Discover amazing places to visit',
                'color' => '#8BC34A',
                'sort_order' => 2,
            ],
            [
                'name' => 'Adventure',
                'slug' => 'adventure',
                'description' => 'Thrilling outdoor adventures',
                'color' => '#FF5722',
                'sort_order' => 3,
            ],
            [
                'name' => 'Culture',
                'slug' => 'culture',
                'description' => 'Local culture and traditions',
                'color' => '#9C27B0',
                'sort_order' => 4,
            ],
            [
                'name' => 'Food & Cuisine',
                'slug' => 'food-cuisine',
                'description' => 'Culinary experiences and local dishes',
                'color' => '#E91E63',
                'sort_order' => 5,
            ],
            [
                'name' => 'Nature',
                'slug' => 'nature',
                'description' => 'Natural landscapes and wildlife',
                'color' => '#4CAF50',
                'sort_order' => 6,
            ],
        ];

        foreach ($categories as $categoryData) {
            BlogCategory::updateOrCreate(
                ['slug' => $categoryData['slug']],
                $categoryData
            );
        }
    }
}
