<?php

namespace Database\Seeders;

use App\Models\PlatformSettings;
use Illuminate\Database\Seeder;

class DestinationsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = PlatformSettings::first();

        if (! $settings) {
            $this->command->error('PlatformSettings not found. Run PlatformSettingsSeeder first.');
            return;
        }

        $settings->update([
            'featured_destinations' => [
                // 1. Houmt Souk / Riadh
                [
                    'id' => 'houmt-souk',
                    'name' => 'Houmt Souk',
                    'description_en' => 'Cultural heart of Djerba',
                    'description_fr' => 'Cœur culturel de Djerba',
                    'image' => '',
                    'link' => '',
                    'seo_title_en' => 'Houmt Souk — Cultural Heart of Djerba | Djerba Fun',
                    'seo_title_fr' => 'Houmt Souk — Cœur culturel de Djerba | Djerba Fun',
                    'seo_description_en' => 'Explore Houmt Souk, the vibrant capital of Djerba island. Discover traditional souks, historic mosques, fondouks, and the famous El Ghriba Synagogue.',
                    'seo_description_fr' => 'Explorez Houmt Souk, la capitale vibrante de l\'île de Djerba. Découvrez les souks traditionnels, les mosquées historiques, les fondouks et la célèbre Synagogue El Ghriba.',
                    'seo_text_en' => 'Houmt Souk is the main town and commercial center of Djerba island. Known for its bustling souks, whitewashed streets, and traditional fondouks (caravanserais), this charming town offers an authentic glimpse into Tunisian island life. The town is home to the Borj El Kebir fortress, numerous mosques, and serves as the gateway to the island\'s cultural heritage. The daily fish market and handicraft souks make it a must-visit destination for any Djerba traveler.',
                    'seo_text_fr' => 'Houmt Souk est la ville principale et le centre commercial de l\'île de Djerba. Connue pour ses souks animés, ses rues blanchies à la chaux et ses fondouks traditionnels (caravansérails), cette ville charmante offre un aperçu authentique de la vie insulaire tunisienne. La ville abrite la forteresse Borj El Kebir, de nombreuses mosquées et sert de porte d\'entrée au patrimoine culturel de l\'île. Le marché aux poissons quotidien et les souks artisanaux en font une destination incontournable pour tout voyageur à Djerba.',
                    'highlights' => [
                        [
                            'icon' => 'shopping-bag',
                            'title_en' => 'Traditional Souks',
                            'title_fr' => 'Souks traditionnels',
                            'description_en' => 'Browse colorful handicrafts, spices, ceramics and textiles in the bustling marketplace',
                            'description_fr' => 'Parcourez les artisanats colorés, épices, céramiques et textiles dans le marché animé',
                        ],
                        [
                            'icon' => 'landmark',
                            'title_en' => 'Borj El Kebir Fortress',
                            'title_fr' => 'Forteresse Borj El Kebir',
                            'description_en' => '15th-century Ottoman fortress overlooking the harbor with panoramic sea views',
                            'description_fr' => 'Forteresse ottomane du XVe siècle surplombant le port avec vue panoramique sur la mer',
                        ],
                        [
                            'icon' => 'home',
                            'title_en' => 'Historic Fondouks',
                            'title_fr' => 'Fondouks historiques',
                            'description_en' => 'Beautifully restored caravanserais now housing artisan workshops and boutiques',
                            'description_fr' => 'Caravansérails magnifiquement restaurés abritant ateliers d\'artisans et boutiques',
                        ],
                        [
                            'icon' => 'utensils-crossed',
                            'title_en' => 'Daily Fish Market',
                            'title_fr' => 'Marché aux poissons',
                            'description_en' => 'Fresh catch auctioned daily at the lively harbourside fish market',
                            'description_fr' => 'Pêche fraîche vendue aux enchères quotidiennement au marché aux poissons du port',
                        ],
                    ],
                    'key_facts' => [
                        [
                            'icon' => 'users',
                            'label_en' => 'Population',
                            'label_fr' => 'Population',
                            'value' => '~75,000',
                        ],
                        [
                            'icon' => 'map-pin',
                            'label_en' => 'Region',
                            'label_fr' => 'Région',
                            'value' => 'Medenine',
                        ],
                        [
                            'icon' => 'star',
                            'label_en' => 'Known for',
                            'label_fr' => 'Connu pour',
                            'value' => 'Souks & Culture',
                        ],
                    ],
                    'gallery' => [],
                    'points_of_interest' => [
                        [
                            'name_en' => 'El Ghriba Synagogue',
                            'name_fr' => 'Synagogue El Ghriba',
                            'description_en' => 'One of the oldest synagogues in the world, dating back over 2,500 years. A major pilgrimage site and architectural marvel.',
                            'description_fr' => 'L\'une des plus anciennes synagogues au monde, datant de plus de 2 500 ans. Un lieu de pèlerinage majeur et une merveille architecturale.',
                        ],
                        [
                            'name_en' => 'Borj El Kebir',
                            'name_fr' => 'Borj El Kebir',
                            'description_en' => 'Imposing 15th-century fortress built by the Hafsid dynasty, offering panoramic views of the harbor and the Mediterranean.',
                            'description_fr' => 'Imposante forteresse du XVe siècle construite par la dynastie hafside, offrant des vues panoramiques sur le port et la Méditerranée.',
                        ],
                        [
                            'name_en' => 'Houmt Souk Souks',
                            'name_fr' => 'Souks de Houmt Souk',
                            'description_en' => 'Labyrinthine market streets filled with pottery, jewelry, leather goods, and traditional Djerbian crafts.',
                            'description_fr' => 'Rues de marché labyrinthiques remplies de poteries, bijoux, articles en cuir et artisanat djerbien traditionnel.',
                        ],
                    ],
                ],

                // 2. Mezraya
                [
                    'id' => 'mezraya',
                    'name' => 'Mezraya',
                    'description_en' => 'Activities hub & tourist zone',
                    'description_fr' => 'Pôle d\'activités & zone touristique',
                    'image' => '',
                    'link' => '',
                    'seo_title_en' => 'Mezraya — Activities Hub of Djerba | Djerba Fun',
                    'seo_title_fr' => 'Mezraya — Pôle d\'activités de Djerba | Djerba Fun',
                    'seo_description_en' => 'Discover Mezraya, Djerba\'s main tourist zone. Horse rides, buggy tours, quad adventures and stunning beaches await you.',
                    'seo_description_fr' => 'Découvrez Mezraya, la principale zone touristique de Djerba. Balades à cheval, tours en buggy, aventures en quad et plages magnifiques vous attendent.',
                    'seo_text_en' => 'Mezraya is the heart of Djerba\'s tourist zone, located along the island\'s northeastern coast. This area is the departure point for many of the island\'s most popular outdoor activities including horseback riding, buggy excursions, quad adventures, and calèche (horse-drawn carriage) rides through the countryside. The area offers a perfect blend of adventure and relaxation, with easy access to beautiful beaches, traditional Djerbian villages, and the famous lagoon where pink flamingos can be spotted.',
                    'seo_text_fr' => 'Mezraya est le cœur de la zone touristique de Djerba, située le long de la côte nord-est de l\'île. Cette zone est le point de départ de nombreuses activités de plein air les plus populaires de l\'île, notamment les balades à cheval, les excursions en buggy, les aventures en quad et les promenades en calèche à travers la campagne. La zone offre un mélange parfait d\'aventure et de détente, avec un accès facile aux belles plages, aux villages djerbiens traditionnels et à la fameuse lagune où l\'on peut observer des flamants roses.',
                    'highlights' => [
                        [
                            'icon' => 'compass',
                            'title_en' => 'Adventure Activities',
                            'title_fr' => 'Activités d\'aventure',
                            'description_en' => 'Quad, buggy, horseback riding and calèche rides departing daily',
                            'description_fr' => 'Quad, buggy, balade à cheval et calèche au départ quotidien',
                        ],
                        [
                            'icon' => 'waves',
                            'title_en' => 'Beautiful Beaches',
                            'title_fr' => 'Belles plages',
                            'description_en' => 'Easy access to some of Djerba\'s finest sandy beaches and turquoise waters',
                            'description_fr' => 'Accès facile aux plus belles plages de sable et eaux turquoise de Djerba',
                        ],
                        [
                            'icon' => 'bird',
                            'title_en' => 'Pink Flamingos',
                            'title_fr' => 'Flamants roses',
                            'description_en' => 'Nearby lagoon is home to colonies of pink flamingos visible year-round',
                            'description_fr' => 'La lagune voisine abrite des colonies de flamants roses visibles toute l\'année',
                        ],
                        [
                            'icon' => 'home',
                            'title_en' => 'Traditional Menzels',
                            'title_fr' => 'Menzels traditionnels',
                            'description_en' => 'Discover the unique Djerbian rural architecture of traditional Menzel houses',
                            'description_fr' => 'Découvrez l\'architecture rurale unique des maisons Menzel traditionnelles djerbiennes',
                        ],
                    ],
                    'key_facts' => [
                        [
                            'icon' => 'map-pin',
                            'label_en' => 'Location',
                            'label_fr' => 'Localisation',
                            'value' => 'NE Djerba',
                        ],
                        [
                            'icon' => 'compass',
                            'label_en' => 'Best for',
                            'label_fr' => 'Idéal pour',
                            'value' => 'Activities',
                        ],
                        [
                            'icon' => 'calendar',
                            'label_en' => 'Best season',
                            'label_fr' => 'Meilleure saison',
                            'value' => 'Mar–Nov',
                        ],
                    ],
                    'gallery' => [],
                    'points_of_interest' => [
                        [
                            'name_en' => 'Flamingo Lagoon',
                            'name_fr' => 'Lagune des flamants roses',
                            'description_en' => 'A natural lagoon where large colonies of pink flamingos gather, especially beautiful at sunset.',
                            'description_fr' => 'Une lagune naturelle où de grandes colonies de flamants roses se rassemblent, particulièrement belle au coucher du soleil.',
                        ],
                        [
                            'name_en' => 'Olive Groves',
                            'name_fr' => 'Oliveraies',
                            'description_en' => 'Ancient olive groves dotting the countryside, some trees dating back hundreds of years.',
                            'description_fr' => 'Anciennes oliveraies parsemant la campagne, certains arbres datant de centaines d\'années.',
                        ],
                        [
                            'name_en' => 'Sidi Mahrez Beach',
                            'name_fr' => 'Plage de Sidi Mahrez',
                            'description_en' => 'One of Djerba\'s most famous beaches with fine white sand and crystal-clear turquoise waters.',
                            'description_fr' => 'L\'une des plages les plus célèbres de Djerba avec du sable blanc fin et des eaux turquoise cristallines.',
                        ],
                    ],
                ],

                // 3. Guellala
                [
                    'id' => 'guellala',
                    'name' => 'Guellala',
                    'description_en' => 'Pottery village & traditional crafts',
                    'description_fr' => 'Village des potiers & artisanat traditionnel',
                    'image' => '',
                    'link' => '',
                    'seo_title_en' => 'Guellala — Pottery Village of Djerba | Djerba Fun',
                    'seo_title_fr' => 'Guellala — Village des potiers de Djerba | Djerba Fun',
                    'seo_description_en' => 'Visit Guellala, the ancestral pottery village of Djerba. Discover centuries-old ceramic traditions, the Heritage Museum, and panoramic island views.',
                    'seo_description_fr' => 'Visitez Guellala, le village ancestral des potiers de Djerba. Découvrez les traditions céramiques séculaires, le Musée du Patrimoine et les vues panoramiques sur l\'île.',
                    'seo_text_en' => 'Guellala is a picturesque village perched on the southern hills of Djerba, famous for its centuries-old pottery tradition. For over 2,000 years, local artisans have been crafting distinctive clay pottery using techniques passed down through generations. The village is also home to the Guellala Heritage Museum, one of the most important cultural institutions on the island, showcasing traditional Djerbian life, crafts, and customs. From the hilltop, visitors enjoy breathtaking panoramic views of the island and the sea.',
                    'seo_text_fr' => 'Guellala est un village pittoresque perché sur les collines du sud de Djerba, célèbre pour sa tradition potière séculaire. Depuis plus de 2 000 ans, les artisans locaux fabriquent de la poterie en argile distinctive en utilisant des techniques transmises de génération en génération. Le village abrite également le Musée du Patrimoine de Guellala, l\'une des institutions culturelles les plus importantes de l\'île, présentant la vie, l\'artisanat et les coutumes traditionnels djerbiens. Du sommet de la colline, les visiteurs profitent de vues panoramiques à couper le souffle sur l\'île et la mer.',
                    'highlights' => [
                        [
                            'icon' => 'palette',
                            'title_en' => 'Ancient Pottery Tradition',
                            'title_fr' => 'Tradition potière ancestrale',
                            'description_en' => 'Over 2,000 years of ceramic craftsmanship with workshops open to visitors',
                            'description_fr' => 'Plus de 2 000 ans d\'artisanat céramique avec des ateliers ouverts aux visiteurs',
                        ],
                        [
                            'icon' => 'landmark',
                            'title_en' => 'Heritage Museum',
                            'title_fr' => 'Musée du Patrimoine',
                            'description_en' => 'Comprehensive museum showcasing traditional Djerbian life and customs',
                            'description_fr' => 'Musée complet présentant la vie et les coutumes traditionnelles djerbiennes',
                        ],
                        [
                            'icon' => 'eye',
                            'title_en' => 'Panoramic Views',
                            'title_fr' => 'Vues panoramiques',
                            'description_en' => 'Hilltop location offering stunning views of the island and Mediterranean Sea',
                            'description_fr' => 'Emplacement en hauteur offrant des vues imprenables sur l\'île et la Méditerranée',
                        ],
                        [
                            'icon' => 'layers',
                            'title_en' => 'Underground Workshops',
                            'title_fr' => 'Ateliers souterrains',
                            'description_en' => 'Traditional underground clay kilns where pottery is fired using ancestral methods',
                            'description_fr' => 'Fours à argile souterrains traditionnels où la poterie est cuite selon des méthodes ancestrales',
                        ],
                    ],
                    'key_facts' => [
                        [
                            'icon' => 'map-pin',
                            'label_en' => 'Location',
                            'label_fr' => 'Localisation',
                            'value' => 'South Djerba',
                        ],
                        [
                            'icon' => 'star',
                            'label_en' => 'Known for',
                            'label_fr' => 'Connu pour',
                            'value' => 'Pottery',
                        ],
                        [
                            'icon' => 'ruler',
                            'label_en' => 'Altitude',
                            'label_fr' => 'Altitude',
                            'value' => '52m',
                        ],
                    ],
                    'gallery' => [],
                    'points_of_interest' => [
                        [
                            'name_en' => 'Guellala Heritage Museum',
                            'name_fr' => 'Musée du Patrimoine de Guellala',
                            'description_en' => 'A fascinating museum built into the hillside, displaying traditional Djerbian life, wedding customs, religious practices, and artisan crafts.',
                            'description_fr' => 'Un musée fascinant construit dans la colline, présentant la vie traditionnelle djerbienne, les coutumes de mariage, les pratiques religieuses et l\'artisanat.',
                        ],
                        [
                            'name_en' => 'Pottery Workshops',
                            'name_fr' => 'Ateliers de poterie',
                            'description_en' => 'Visit active workshops where artisans demonstrate traditional pottery-making techniques and sell their creations.',
                            'description_fr' => 'Visitez des ateliers actifs où les artisans démontrent les techniques traditionnelles de fabrication de poterie et vendent leurs créations.',
                        ],
                        [
                            'name_en' => 'Hilltop Viewpoint',
                            'name_fr' => 'Point de vue panoramique',
                            'description_en' => 'The highest point of the village offering 360-degree views of the island, the sea, and the mainland coast.',
                            'description_fr' => 'Le point le plus élevé du village offrant des vues à 360 degrés sur l\'île, la mer et la côte continentale.',
                        ],
                    ],
                ],
            ],
        ]);

        $this->command->info('Featured destinations seeded: Houmt Souk, Mezraya, Guellala');
    }
}
