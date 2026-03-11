<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformSettings;
use Illuminate\Database\Seeder;

class BrandPillarsSeeder extends Seeder
{
    /**
     * Seed the brand pillar titles and descriptions for the homepage.
     */
    public function run(): void
    {
        $settings = PlatformSettings::first();

        if (! $settings) {
            $this->command->error('PlatformSettings not found. Run PlatformSettingsSeeder first.');

            return;
        }

        $settings->update([
            // Pillar 1 — Water Thrills
            'pillar_1_title' => [
                'en' => 'Water Thrills',
                'fr' => 'Sensations Nautiques',
            ],
            'pillar_1_description' => [
                'en' => 'Jet ski, diving, parasailing — experience the Mediterranean like never before',
                'fr' => 'Jet ski, plongée, parachute ascensionnel — vivez la Méditerranée autrement',
            ],

            // Pillar 2 — Heritage & Culture
            'pillar_2_title' => [
                'en' => 'Heritage & Culture',
                'fr' => 'Patrimoine & Culture',
            ],
            'pillar_2_description' => [
                'en' => 'Ancient souks, Guellala pottery, El Ghriba Synagogue — 3000 years of history',
                'fr' => 'Souks ancestraux, poteries de Guellala, synagogue El Ghriba — 3000 ans d\'histoire',
            ],

            // Pillar 3 — Land Adventures
            'pillar_3_title' => [
                'en' => 'Land Adventures',
                'fr' => 'Aventures Terrestres',
            ],
            'pillar_3_description' => [
                'en' => 'Quad rides in the dunes, horse carriage in Mezraya, hikes to pink flamingos',
                'fr' => 'Quad dans les dunes, calèche à Mezraya, randonnée aux flamants roses',
            ],
        ]);

        $this->command->info('Brand pillars seeded successfully!');
    }
}
