<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ActivityType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ActivityTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Activity types matching go-adventure.net (old site)
        $activityTypes = [
            [
                'name' => ['en' => 'Cultural Expeditions', 'fr' => 'Expéditions Culturelles'],
                'slug' => 'cultural-expeditions',
                'description' => [
                    'en' => 'Discover local culture, heritage sites, and traditional customs',
                    'fr' => 'Découvrez la culture locale, les sites patrimoniaux et les coutumes traditionnelles',
                ],
                'icon' => 'heroicon-o-building-library',
                'color' => '#8B4513', // Brown
                'display_order' => 1,
            ],
            [
                'name' => ['en' => 'Corporate & Team Building Stays', 'fr' => 'Séjours Corporate & Team Building'],
                'slug' => 'corporate-sports',
                'description' => [
                    'en' => 'Team building, corporate retreats, and sports-focused group experiences',
                    'fr' => 'Team building, séminaires d\'entreprise et expériences de groupe axées sur le sport',
                ],
                'icon' => 'heroicon-o-building-office',
                'color' => '#4169E1', // Royal Blue
                'display_order' => 2,
            ],
            [
                'name' => ['en' => 'Road & Mountain Biking', 'fr' => 'Vélo de Route & de Montagne'],
                'slug' => 'mountain-biking',
                'description' => [
                    'en' => 'Explore scenic trails and paths on two wheels',
                    'fr' => 'Explorez des sentiers et des chemins pittoresques à vélo',
                ],
                'icon' => 'heroicon-o-sparkles',
                'color' => '#228B22', // Forest Green
                'display_order' => 3,
            ],
            [
                'name' => ['en' => 'Water Activities & Sports', 'fr' => 'Activités & Sports Nautiques'],
                'slug' => 'water-activities',
                'description' => [
                    'en' => 'Enjoy water sports, diving, sailing, and coastal adventures',
                    'fr' => 'Profitez des sports nautiques, de la plongée, de la voile et des aventures côtières',
                ],
                'icon' => 'heroicon-o-lifebuoy',
                'color' => '#1E90FF', // Dodger Blue
                'display_order' => 4,
            ],
            [
                'name' => ['en' => 'Trail Running, Hiking & Trekking', 'fr' => 'Trail Running, Randonnée & Trekking'],
                'slug' => 'trail-trekking',
                'description' => [
                    'en' => 'Hiking adventures through mountains, deserts, and natural landscapes',
                    'fr' => 'Aventures de randonnée à travers les montagnes, les déserts et les paysages naturels',
                ],
                'icon' => 'heroicon-o-map',
                'color' => '#0D642E', // Primary Green (brand color)
                'display_order' => 5,
            ],
        ];

        foreach ($activityTypes as $type) {
            ActivityType::updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'display_order' => $type['display_order'],
                    'is_active' => true,
                    'listings_count' => 0,
                ]
            );
        }

        $this->command->info('Activity types seeded successfully!');
    }
}
