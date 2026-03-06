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
        // Activity types for Evasion Djerba - Djerba island tourism
        $activityTypes = [
            [
                'name' => ['en' => 'Island Tours', 'fr' => 'Tours de l\'île'],
                'slug' => 'island-tours',
                'description' => [
                    'en' => 'Discover Djerba island with guided tours, horse carriage rides, and quad adventures',
                    'fr' => 'Découvrez l\'île de Djerba avec des visites guidées, des balades en calèche et des aventures en quad',
                ],
                'icon' => 'heroicon-o-map',
                'color' => '#0077B6', // Ocean Blue (brand primary)
                'display_order' => 1,
            ],
            [
                'name' => ['en' => 'Nautical Activities', 'fr' => 'Activités Nautiques'],
                'slug' => 'nautical-activities',
                'description' => [
                    'en' => 'Jet ski, parasailing, diving, banana boat, and other water sports',
                    'fr' => 'Jet ski, parachute ascensionnel, plongée, banana boat et autres sports nautiques',
                ],
                'icon' => 'heroicon-o-lifebuoy',
                'color' => '#0096C7', // Ocean Blue Light
                'display_order' => 2,
            ],
            [
                'name' => ['en' => 'Beach & Relaxation', 'fr' => 'Plage & Détente'],
                'slug' => 'beach-relaxation',
                'description' => [
                    'en' => 'Beach clubs, sunset cruises, and relaxation experiences',
                    'fr' => 'Beach clubs, croisières au coucher du soleil et expériences de détente',
                ],
                'icon' => 'heroicon-o-sun',
                'color' => '#F4A261', // Sandy Orange (brand secondary)
                'display_order' => 3,
            ],
            [
                'name' => ['en' => 'Cultural Heritage', 'fr' => 'Patrimoine Culturel'],
                'slug' => 'cultural-heritage',
                'description' => [
                    'en' => 'Explore Djerba\'s synagogues, museums, Houmt Souk, and traditional crafts',
                    'fr' => 'Explorez les synagogues, musées, Houmt Souk et l\'artisanat traditionnel de Djerba',
                ],
                'icon' => 'heroicon-o-building-library',
                'color' => '#023E8A', // Ocean Blue Dark
                'display_order' => 4,
            ],
            [
                'name' => ['en' => 'Local Gastronomy', 'fr' => 'Gastronomie Locale'],
                'slug' => 'local-gastronomy',
                'description' => [
                    'en' => 'Taste authentic Djerbian cuisine, cooking classes, and food tours',
                    'fr' => 'Dégustez la cuisine djerbienne authentique, cours de cuisine et visites gastronomiques',
                ],
                'icon' => 'heroicon-o-sparkles',
                'color' => '#E76F51', // Sandy Orange Dark
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
