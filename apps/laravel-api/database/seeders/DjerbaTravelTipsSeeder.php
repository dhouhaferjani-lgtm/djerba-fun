<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TravelTip;
use Illuminate\Database\Seeder;

class DjerbaTravelTipsSeeder extends Seeder
{
    /**
     * Replace generic travel tips with Djerba Fun–specific tips.
     */
    public function run(): void
    {
        // Remove existing generic tips
        TravelTip::truncate();

        $tips = [
            [
                'content' => [
                    'en' => 'Book your jet ski adventure in Houmt Souk — ride the turquoise Mediterranean!',
                    'fr' => 'Réservez votre aventure en jet ski à Houmt Souk — surfez sur la Méditerranée turquoise !',
                ],
                'display_order' => 1,
            ],
            [
                'content' => [
                    'en' => 'Explore Guellala\'s pottery workshops — 3000 years of artisan tradition',
                    'fr' => 'Explorez les ateliers de poterie de Guellala — 3000 ans de tradition artisanale',
                ],
                'display_order' => 2,
            ],
            [
                'content' => [
                    'en' => 'Take a horse carriage ride through Mezraya — the most scenic route in Djerba',
                    'fr' => 'Prenez une calèche à travers Mezraya — le parcours le plus pittoresque de Djerba',
                ],
                'display_order' => 3,
            ],
            [
                'content' => [
                    'en' => 'Watch flamingos at the lagoon — best seen at sunrise from Ras R\'mel',
                    'fr' => 'Observez les flamants roses à la lagune — à admirer au lever du soleil depuis Ras R\'mel',
                ],
                'display_order' => 4,
            ],
            [
                'content' => [
                    'en' => 'Try parasailing at Sidi Mahrez beach — see Djerba from above!',
                    'fr' => 'Essayez le parachute ascensionnel à la plage Sidi Mahrez — voyez Djerba depuis les airs !',
                ],
                'display_order' => 5,
            ],
            [
                'content' => [
                    'en' => 'Don\'t miss a fresh brik at the Houmt Souk fish market — best breakfast on the island',
                    'fr' => 'Ne manquez pas un brik frais au marché aux poissons de Houmt Souk — le meilleur petit-déjeuner de l\'île',
                ],
                'display_order' => 6,
            ],
            [
                'content' => [
                    'en' => 'Dive at Borj Castille reef — Djerba\'s hidden underwater paradise',
                    'fr' => 'Plongez au récif de Borj Castille — le paradis sous-marin caché de Djerba',
                ],
                'display_order' => 7,
            ],
        ];

        foreach ($tips as $tip) {
            TravelTip::create([
                'content' => $tip['content'],
                'is_active' => true,
                'display_order' => $tip['display_order'],
            ]);
        }

        $this->command->info('Created ' . count($tips) . ' Djerba travel tips');
    }
}
