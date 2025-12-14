<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'name' => ['en' => 'Djerba', 'fr' => 'Djerba'],
                'slug' => 'djerba',
                'description' => [
                    'en' => 'A beautiful Mediterranean island known for its stunning beaches, ancient history, and unique architecture. Djerba offers a perfect blend of relaxation and adventure.',
                    'fr' => 'Une magnifique île méditerranéenne connue pour ses plages magnifiques, son histoire ancienne et son architecture unique. Djerba offre un mélange parfait de détente et d\'aventure.',
                ],
                'latitude' => 33.8076,
                'longitude' => 10.8451,
                'address' => 'Djerba Island',
                'city' => 'Houmt Souk',
                'region' => 'Medenine',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=800',
            ],
            [
                'name' => ['en' => 'Sahara Desert', 'fr' => 'Désert du Sahara'],
                'slug' => 'sahara-desert',
                'description' => [
                    'en' => 'Experience the majestic Tunisian Sahara with its golden dunes, starlit nights, and authentic Berber culture. An unforgettable adventure awaits.',
                    'fr' => 'Découvrez le majestueux Sahara tunisien avec ses dunes dorées, ses nuits étoilées et sa culture berbère authentique. Une aventure inoubliable vous attend.',
                ],
                'latitude' => 32.9167,
                'longitude' => 9.4167,
                'address' => 'Douz',
                'city' => 'Douz',
                'region' => 'Kebili',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=800',
            ],
            [
                'name' => ['en' => 'Tunis', 'fr' => 'Tunis'],
                'slug' => 'tunis',
                'description' => [
                    'en' => 'The vibrant capital city blending ancient medina charm with modern culture. Explore historic souks, stunning mosques, and world-class museums.',
                    'fr' => 'La capitale vibrante mêlant le charme de la médina ancienne à la culture moderne. Explorez les souks historiques, les mosquées magnifiques et les musées de classe mondiale.',
                ],
                'latitude' => 36.8065,
                'longitude' => 10.1815,
                'address' => 'Tunis Medina',
                'city' => 'Tunis',
                'region' => 'Tunis',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1590492106698-05dc0e19fb26?w=800',
            ],
            [
                'name' => ['en' => 'Sidi Bou Said', 'fr' => 'Sidi Bou Saïd'],
                'slug' => 'sidi-bou-said',
                'description' => [
                    'en' => 'The iconic blue and white village perched on a cliff overlooking the Mediterranean. Famous for its artistic heritage and breathtaking views.',
                    'fr' => 'L\'emblématique village bleu et blanc perché sur une falaise surplombant la Méditerranée. Célèbre pour son patrimoine artistique et ses vues à couper le souffle.',
                ],
                'latitude' => 36.8689,
                'longitude' => 10.3417,
                'address' => 'Sidi Bou Said',
                'city' => 'Sidi Bou Said',
                'region' => 'Tunis',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1568797629192-789acf8e4df3?w=800',
            ],
            [
                'name' => ['en' => 'Tozeur', 'fr' => 'Tozeur'],
                'slug' => 'tozeur',
                'description' => [
                    'en' => 'A desert oasis city known for its palm groves, traditional brick architecture, and as a gateway to Star Wars filming locations.',
                    'fr' => 'Une ville oasis du désert connue pour ses palmeraies, son architecture traditionnelle en briques et comme porte d\'entrée vers les lieux de tournage de Star Wars.',
                ],
                'latitude' => 33.9197,
                'longitude' => 8.1339,
                'address' => 'Tozeur',
                'city' => 'Tozeur',
                'region' => 'Tozeur',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1548020920-3e8e6d2b7d0e?w=800',
            ],
            [
                'name' => ['en' => 'Carthage', 'fr' => 'Carthage'],
                'slug' => 'carthage',
                'description' => [
                    'en' => 'Walk through the ruins of one of history\'s most powerful civilizations. A UNESCO World Heritage site with incredible Roman and Punic remains.',
                    'fr' => 'Parcourez les ruines de l\'une des civilisations les plus puissantes de l\'histoire. Un site du patrimoine mondial de l\'UNESCO avec d\'incroyables vestiges romains et puniques.',
                ],
                'latitude' => 36.8528,
                'longitude' => 10.3233,
                'address' => 'Carthage',
                'city' => 'Carthage',
                'region' => 'Tunis',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=800',
            ],
            [
                'name' => ['en' => 'Kairouan', 'fr' => 'Kairouan'],
                'slug' => 'kairouan',
                'description' => [
                    'en' => 'The fourth holiest city in Islam, famous for its Great Mosque and rich spiritual heritage. A center of Islamic culture and traditional crafts.',
                    'fr' => 'La quatrième ville sainte de l\'Islam, célèbre pour sa Grande Mosquée et son riche patrimoine spirituel. Un centre de culture islamique et d\'artisanat traditionnel.',
                ],
                'latitude' => 35.6781,
                'longitude' => 10.0963,
                'address' => 'Kairouan',
                'city' => 'Kairouan',
                'region' => 'Kairouan',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=800',
            ],
            [
                'name' => ['en' => 'Matmata', 'fr' => 'Matmata'],
                'slug' => 'matmata',
                'description' => [
                    'en' => 'Famous for its unique underground troglodyte homes and as Luke Skywalker\'s home planet Tatooine in Star Wars.',
                    'fr' => 'Célèbre pour ses maisons troglodytes souterraines uniques et comme planète natale de Luke Skywalker, Tatooine, dans Star Wars.',
                ],
                'latitude' => 33.5447,
                'longitude' => 9.9672,
                'address' => 'Matmata',
                'city' => 'Matmata',
                'region' => 'Gabes',
                'country' => 'TN',
                'timezone' => 'Africa/Tunis',
                'image_url' => 'https://images.unsplash.com/photo-1548020920-3e8e6d2b7d0e?w=800',
            ],
        ];

        foreach ($locations as $locationData) {
            Location::create($locationData);
        }
    }
}
