<?php

namespace Database\Seeders;

use App\Models\TravelTip;
use Illuminate\Database\Seeder;

class TravelTipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tips = [
            [
                'content' => [
                    'en' => 'Best time to visit the Sahara is October to April when temperatures are milder',
                    'fr' => 'La meilleure période pour visiter le Sahara est d\'octobre à avril, lorsque les températures sont plus douces',
                ],
                'display_order' => 1,
            ],
            [
                'content' => [
                    'en' => 'Always carry plenty of water when exploring desert regions - stay hydrated!',
                    'fr' => 'Emportez toujours beaucoup d\'eau lors de l\'exploration des régions désertiques - restez hydraté !',
                ],
                'display_order' => 2,
            ],
            [
                'content' => [
                    'en' => 'Pack layers! Desert temperatures can drop significantly at night',
                    'fr' => 'Prévoyez des couches de vêtements ! Les températures du désert peuvent chuter considérablement la nuit',
                ],
                'display_order' => 3,
            ],
            [
                'content' => [
                    'en' => 'Try local Tunisian mint tea - it\'s a symbol of hospitality and friendship',
                    'fr' => 'Goûtez le thé à la menthe tunisien - c\'est un symbole d\'hospitalité et d\'amitié',
                ],
                'display_order' => 4,
            ],
            [
                'content' => [
                    'en' => 'Book eco-friendly tours to help preserve Tunisia\'s natural beauty for future generations',
                    'fr' => 'Réservez des circuits écologiques pour aider à préserver la beauté naturelle de la Tunisie pour les générations futures',
                ],
                'display_order' => 5,
            ],
            [
                'content' => [
                    'en' => 'Visit Djerba island for stunning beaches and unique architecture',
                    'fr' => 'Visitez l\'île de Djerba pour ses plages magnifiques et son architecture unique',
                ],
                'display_order' => 6,
            ],
            [
                'content' => [
                    'en' => 'The ancient city of Carthage is a UNESCO World Heritage Site - don\'t miss it!',
                    'fr' => 'L\'ancienne ville de Carthage est un site du patrimoine mondial de l\'UNESCO - ne la manquez pas !',
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
    }
}
