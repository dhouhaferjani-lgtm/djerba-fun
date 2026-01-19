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
                'name' => ['en' => 'Travel Tips', 'fr' => 'Conseils de Voyage'],
                'slug' => 'travel-tips',
                'description' => ['en' => 'Practical travel advice and tips', 'fr' => 'Conseils pratiques de voyage'],
                'color' => '#0D642E',
                'sort_order' => 1,
            ],
            [
                'name' => ['en' => 'Destinations', 'fr' => 'Destinations'],
                'slug' => 'destinations',
                'description' => ['en' => 'Discover amazing places to visit', 'fr' => 'Découvrez des endroits incroyables'],
                'color' => '#8BC34A',
                'sort_order' => 2,
            ],
            [
                'name' => ['en' => 'Adventure', 'fr' => 'Aventure'],
                'slug' => 'adventure',
                'description' => ['en' => 'Thrilling outdoor adventures', 'fr' => 'Aventures palpitantes en plein air'],
                'color' => '#FF5722',
                'sort_order' => 3,
            ],
            [
                'name' => ['en' => 'Culture', 'fr' => 'Culture'],
                'slug' => 'culture',
                'description' => ['en' => 'Local culture and traditions', 'fr' => 'Culture et traditions locales'],
                'color' => '#9C27B0',
                'sort_order' => 4,
            ],
            [
                'name' => ['en' => 'Food & Cuisine', 'fr' => 'Gastronomie'],
                'slug' => 'food-cuisine',
                'description' => ['en' => 'Culinary experiences and local dishes', 'fr' => 'Expériences culinaires et plats locaux'],
                'color' => '#E91E63',
                'sort_order' => 5,
            ],
            [
                'name' => ['en' => 'Nature', 'fr' => 'Nature'],
                'slug' => 'nature',
                'description' => ['en' => 'Natural landscapes and wildlife', 'fr' => 'Paysages naturels et faune'],
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
