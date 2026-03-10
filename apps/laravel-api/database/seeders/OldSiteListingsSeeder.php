<?php

namespace Database\Seeders;

use App\Enums\AvailabilityRuleType;
use App\Enums\ListingStatus;
use App\Enums\MediaCategory;
use App\Enums\ServiceType;
use App\Models\ActivityType;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class OldSiteListingsSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = User::where('email', 'vendor@djerba.fun')->first();
        if (! $vendor) {
            $vendor = User::where('role', 'vendor')->first();
        }
        if (! $vendor) {
            $this->command->error('No vendor found. Run VendorSeeder first.');
            return;
        }

        $djerba = Location::where('slug', 'djerba')->first();
        if (! $djerba) {
            $this->command->error('Djerba location not found. Run LocationSeeder first.');
            return;
        }

        $nauticalActivities = ActivityType::where('slug', 'nautical-activities')->first();

        $nautical = [
            // 1. Plongée Découverte
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Plongée Découverte à Djerba',
                    'en' => 'Discovery Diving in Djerba',
                ],
                'slug' => 'plongee-decouverte-djerba',
                'summary' => [
                    'fr' => 'Découvrez les merveilles du monde sous-marin sans certification. Plongée encadrée jusqu\'à 5 mètres dans les eaux cristallines de Djerba.',
                    'en' => 'Discover the wonders of the underwater world without certification. Supervised dive up to 5 meters in Djerba\'s crystal-clear waters.',
                ],
                'description' => [
                    'fr' => 'Vous rêvez de découvrir les merveilles du monde sous-marin sans certification ? La plongée découverte est faite pour vous ! Cette expérience d\'initiation vous associe à un instructeur professionnel pour une exploration sous-marine jusqu\'à 5 mètres de profondeur dans les eaux cristallines de Djerba. Respirez sous l\'eau, ressentez l\'apesanteur et observez la vie marine en toute sécurité. Aucune certification ni formation préalable requise.',
                    'en' => 'Do you dream of discovering the wonders of the underwater world without certification? Discovery diving is for you! This introductory experience pairs you with a professional instructor for underwater exploration up to 5 meters deep in Djerba\'s crystal-clear waters. Breathe underwater, experience weightlessness, and observe marine life safely. No certification or prior training required.',
                ],
                'highlights' => [
                    ['fr' => 'Aucune certification requise', 'en' => 'No certification required'],
                    ['fr' => 'Instructeur professionnel dédié', 'en' => 'Dedicated professional instructor'],
                    ['fr' => 'Eaux cristallines de Djerba', 'en' => 'Djerba\'s crystal-clear waters'],
                    ['fr' => 'Profondeur max 5 mètres', 'en' => 'Max depth 5 meters'],
                ],
                'included' => [
                    ['fr' => 'Équipement de plongée complet', 'en' => 'Full diving equipment'],
                    ['fr' => 'Instructeur professionnel', 'en' => 'Professional instructor'],
                    ['fr' => 'Briefing sécurité', 'en' => 'Safety briefing'],
                ],
                'not_included' => [
                    ['fr' => 'Transport aller-retour', 'en' => 'Round-trip transport'],
                    ['fr' => 'Photos sous-marines', 'en' => 'Underwater photos'],
                ],
                'requirements' => [
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                ],
                'safety_info' => [
                    'minimum_age' => 10,
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par personne', 'en' => 'Per person'],
                            'tnd_price' => 135,
                            'eur_price' => 45,
                        ],
                    ],
                ],
                'duration' => ['value' => 60, 'unit' => 'minutes'],
                'difficulty' => 'easy',
                'min_group_size' => 1,
                'max_group_size' => 10,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/12/04/div.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/12/04/div3.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/12/04/div4.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/12/04/div2.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/12/04/div1.jpg',
                ],
            ],

            // 2. Jetski 15/30 min
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Jetski 15/30 min',
                    'en' => 'Jetski 15/30 min',
                ],
                'slug' => 'jetski-15-30-min',
                'summary' => [
                    'fr' => 'Session de jetski encadrée par un moniteur diplômé d\'État. Aucun permis bateau requis.',
                    'en' => 'Jetski session supervised by a state-certified instructor. No boating license required.',
                ],
                'description' => [
                    'fr' => 'Vivez une aventure en jetski avec supervision professionnelle. Une session d\'introduction comprend les instructions d\'un moniteur diplômé d\'État dans une zone de sécurité désignée. Naviguez librement sous la supervision de l\'instructeur. Aucun permis bateau requis. Découvrez aussi notre tour guidé d\'une heure !',
                    'en' => 'Experience a jetski adventure with professional supervision. An introductory session includes guidance from a state-certified instructor within a designated safe area. Navigate freely under instructor supervision. No boating license required. Also discover our one-hour guided tour!',
                ],
                'highlights' => [
                    ['fr' => 'Moniteur diplômé d\'État', 'en' => 'State-certified instructor'],
                    ['fr' => 'Aucun permis requis', 'en' => 'No license required'],
                    ['fr' => 'Zone sécurisée', 'en' => 'Safe designated area'],
                ],
                'included' => [
                    ['fr' => 'Jetski et équipement', 'en' => 'Jetski and equipment'],
                    ['fr' => 'Moniteur diplômé', 'en' => 'Certified instructor'],
                    ['fr' => 'Gilet de sauvetage', 'en' => 'Life jacket'],
                ],
                'not_included' => [
                    ['fr' => 'Photos (15€ en supplément)', 'en' => 'Photos (€15 extra)'],
                    ['fr' => 'Transport aller-retour', 'en' => 'Round-trip transport'],
                ],
                'requirements' => [
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                ],
                'safety_info' => [
                    'minimum_age' => 18,
                    'not_suitable_for' => [
                        ['fr' => 'Femmes enceintes', 'en' => 'Pregnant women'],
                    ],
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par jetski', 'en' => 'Per jetski'],
                            'tnd_price' => 105,
                            'eur_price' => 35,
                        ],
                    ],
                ],
                'duration' => ['value' => 30, 'unit' => 'minutes'],
                'difficulty' => 'moderate',
                'min_group_size' => 1,
                'max_group_size' => 2,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/jet-10.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/jet4.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/jet5.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/jet9.jpg',
                ],
            ],

            // 3. Parasailing
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Parachute Ascensionnel',
                    'en' => 'Parasailing',
                ],
                'slug' => 'parachute-ascensionnel-djerba',
                'summary' => [
                    'fr' => 'Envolez-vous au-dessus des eaux turquoise de Djerba pour 7 à 10 minutes de sensations fortes.',
                    'en' => 'Soar above the turquoise waters of Djerba for 7 to 10 minutes of thrills.',
                ],
                'description' => [
                    'fr' => 'Vivez l\'expérience unique du parachute ascensionnel au-dessus des eaux cristallines de Djerba. Après un briefing sécurité et l\'équipement du gilet de sauvetage, envolez-vous pour 7 à 10 minutes de pur bonheur avec une vue imprenable sur l\'île. Accessible dès 5 ans.',
                    'en' => 'Experience the unique thrill of parasailing above Djerba\'s crystal-clear waters. After a safety briefing and life jacket fitting, soar for 7 to 10 minutes of pure bliss with a breathtaking view of the island. Accessible from age 5.',
                ],
                'highlights' => [
                    ['fr' => 'Vue imprenable sur Djerba', 'en' => 'Breathtaking view of Djerba'],
                    ['fr' => '7-10 minutes de vol', 'en' => '7-10 minutes of flight'],
                    ['fr' => 'Accessible dès 5 ans', 'en' => 'Accessible from age 5'],
                ],
                'included' => [
                    ['fr' => 'Gilet de sauvetage', 'en' => 'Life jacket'],
                    ['fr' => 'Briefing sécurité', 'en' => 'Safety briefing'],
                    ['fr' => 'Supervision instructeur', 'en' => 'Instructor supervision'],
                ],
                'not_included' => [
                    ['fr' => 'Photos (15€ en supplément)', 'en' => 'Photos (€15 extra)'],
                    ['fr' => 'Transport aller-retour', 'en' => 'Round-trip transport'],
                ],
                'requirements' => [
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Savoir nager', 'en' => 'Must know how to swim'],
                ],
                'safety_info' => [
                    'minimum_age' => 5,
                    'not_suitable_for' => [
                        ['fr' => 'Femmes enceintes', 'en' => 'Pregnant women'],
                    ],
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par personne', 'en' => 'Per person'],
                            'tnd_price' => 120,
                            'eur_price' => 40,
                        ],
                    ],
                ],
                'duration' => ['value' => 10, 'unit' => 'minutes'],
                'difficulty' => 'easy',
                'min_group_size' => 1,
                'max_group_size' => 3,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/14/para.jpg',
                ],
            ],

            // 4. Ring
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Ring',
                    'en' => 'Ring Ride',
                ],
                'slug' => 'ring-nautique-djerba',
                'summary' => [
                    'fr' => '8 minutes d\'adrénaline sur l\'eau avec gilet de sauvetage et briefing sécurité.',
                    'en' => '8 minutes of adrenaline on the water with life jacket and safety briefing.',
                ],
                'description' => [
                    'fr' => 'Équipé d\'un gilet de sauvetage et après un briefing sécurité rapide, embarquez pour une aventure pleine d\'adrénaline de 8 minutes sur l\'eau. Une activité rafraîchissante et palpitante pour votre journée à la plage.',
                    'en' => 'Equipped with a life jacket and after a quick safety briefing, embark on an adrenaline-pumping 8-minute adventure on the water. A refreshing and thrilling activity for your beach day.',
                ],
                'highlights' => [
                    ['fr' => '8 minutes d\'adrénaline pure', 'en' => '8 minutes of pure adrenaline'],
                    ['fr' => 'Activité rafraîchissante', 'en' => 'Refreshing activity'],
                    ['fr' => 'Équipement sécurité fourni', 'en' => 'Safety equipment provided'],
                ],
                'included' => [
                    ['fr' => 'Gilet de sauvetage', 'en' => 'Life jacket'],
                    ['fr' => 'Briefing sécurité', 'en' => 'Safety briefing'],
                ],
                'not_included' => [
                    ['fr' => 'Photos (15€ en supplément)', 'en' => 'Photos (€15 extra)'],
                ],
                'requirements' => [
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Savoir nager', 'en' => 'Must know how to swim'],
                ],
                'safety_info' => [
                    'minimum_age' => 12,
                    'not_suitable_for' => [
                        ['fr' => 'Femmes enceintes', 'en' => 'Pregnant women'],
                    ],
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par personne', 'en' => 'Per person'],
                            'tnd_price' => 45,
                            'eur_price' => 15,
                        ],
                    ],
                ],
                'duration' => ['value' => 8, 'unit' => 'minutes'],
                'difficulty' => 'moderate',
                'min_group_size' => 1,
                'max_group_size' => 3,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/14/ring2.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/14/ring.jpg',
                ],
            ],

            // 5. Bob Twister
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Bob Twister',
                    'en' => 'Bob Twister',
                ],
                'slug' => 'bob-twister-djerba',
                'summary' => [
                    'fr' => '8 minutes de sensations fortes sur l\'eau avec instruction professionnelle et équipement de sécurité.',
                    'en' => '8 minutes of thrilling water sports with professional instruction and safety equipment.',
                ],
                'description' => [
                    'fr' => 'Vivez 8 minutes de sensations fortes palpitantes sur l\'eau. Montez à bord avec instruction professionnelle et équipement de sécurité complet. Une expérience pleine d\'adrénaline parfaite pour les amateurs de sensations fortes.',
                    'en' => 'Experience 8 minutes of exhilarating thrills on the water. Board with professional instruction and complete safety equipment. An adrenaline-filled experience perfect for thrill-seekers.',
                ],
                'highlights' => [
                    ['fr' => '8 minutes de sensations fortes', 'en' => '8 minutes of thrills'],
                    ['fr' => 'Instruction professionnelle', 'en' => 'Professional instruction'],
                    ['fr' => 'Équipement sécurité complet', 'en' => 'Complete safety equipment'],
                ],
                'included' => [
                    ['fr' => 'Gilet de sauvetage', 'en' => 'Life jacket'],
                    ['fr' => 'Briefing sécurité', 'en' => 'Safety briefing'],
                    ['fr' => 'Instruction professionnelle', 'en' => 'Professional instruction'],
                ],
                'not_included' => [
                    ['fr' => 'Photos (15€ en supplément)', 'en' => 'Photos (€15 extra)'],
                ],
                'requirements' => [
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Savoir nager', 'en' => 'Must know how to swim'],
                ],
                'safety_info' => [
                    'minimum_age' => 16,
                    'not_suitable_for' => [
                        ['fr' => 'Femmes enceintes', 'en' => 'Pregnant women'],
                    ],
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par personne', 'en' => 'Per person'],
                            'tnd_price' => 60,
                            'eur_price' => 20,
                        ],
                    ],
                ],
                'duration' => ['value' => 8, 'unit' => 'minutes'],
                'difficulty' => 'moderate',
                'min_group_size' => 1,
                'max_group_size' => 6,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/bob.jpg',
                ],
            ],

            // 6. Sofa (Canapé)
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Sofa (Canapé)',
                    'en' => 'Sofa Ride',
                ],
                'slug' => 'sofa-canape-djerba',
                'summary' => [
                    'fr' => 'Activité nautique rafraîchissante et palpitante de 8 minutes, idéale pour votre journée plage.',
                    'en' => 'Refreshing and thrilling 8-minute water activity, perfect for your beach day.',
                ],
                'description' => [
                    'fr' => 'Cette activité nautique offre une expérience rafraîchissante et palpitante pendant votre journée à la plage. Après avoir reçu l\'équipement de sécurité et un briefing, embarquez sur le Sofa pour environ 8 minutes de sensations fortes à grande vitesse sur l\'eau.',
                    'en' => 'This water activity offers a refreshing and thrilling experience during your beach day. After receiving safety equipment and a briefing, board the Sofa for approximately 8 minutes of high-speed thrills on the water.',
                ],
                'highlights' => [
                    ['fr' => '8 minutes de sensations fortes', 'en' => '8 minutes of thrills'],
                    ['fr' => 'Activité rafraîchissante', 'en' => 'Refreshing activity'],
                    ['fr' => 'Accessible dès 10 ans', 'en' => 'Accessible from age 10'],
                ],
                'included' => [
                    ['fr' => 'Gilet de sauvetage', 'en' => 'Life jacket'],
                    ['fr' => 'Briefing sécurité', 'en' => 'Safety briefing'],
                ],
                'not_included' => [
                    ['fr' => 'Photos (15€ en supplément)', 'en' => 'Photos (€15 extra)'],
                ],
                'requirements' => [
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Savoir nager', 'en' => 'Must know how to swim'],
                ],
                'safety_info' => [
                    'minimum_age' => 10,
                    'not_suitable_for' => [
                        ['fr' => 'Femmes enceintes', 'en' => 'Pregnant women'],
                    ],
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par personne', 'en' => 'Per person'],
                            'tnd_price' => 45,
                            'eur_price' => 15,
                        ],
                    ],
                ],
                'duration' => ['value' => 8, 'unit' => 'minutes'],
                'difficulty' => 'easy',
                'min_group_size' => 1,
                'max_group_size' => 6,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/cana2.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/cana.jpg',
                ],
            ],

            // 7. Banana (Banane)
            [
                'service_type' => ServiceType::NAUTICAL,
                'activity_type' => $nauticalActivities,
                'title' => [
                    'fr' => 'Banana (Banane)',
                    'en' => 'Banana Boat Ride',
                ],
                'slug' => 'banana-banane-djerba',
                'summary' => [
                    'fr' => 'Activité nautique fun et rafraîchissante de 8 minutes, idéale pour les groupes et les familles.',
                    'en' => 'Fun and refreshing 8-minute water activity, ideal for groups and families.',
                ],
                'description' => [
                    'fr' => 'Cette activité nautique, adaptée à partir de 12 ans, promet une expérience rafraîchissante et palpitante pendant votre journée à la plage. Recevez votre gilet de sauvetage, un briefing sécurité, et profitez de 8 minutes de sensations fortes sur l\'eau.',
                    'en' => 'This water activity, suitable for ages 12 and up, promises a refreshing and thrilling experience during your beach day. Receive your life jacket, a safety briefing, and enjoy 8 minutes of exhilarating thrills on the water.',
                ],
                'highlights' => [
                    ['fr' => '8 minutes de fun sur l\'eau', 'en' => '8 minutes of fun on the water'],
                    ['fr' => 'Idéal pour les groupes', 'en' => 'Ideal for groups'],
                    ['fr' => 'Accessible dès 12 ans', 'en' => 'Accessible from age 12'],
                ],
                'included' => [
                    ['fr' => 'Gilet de sauvetage', 'en' => 'Life jacket'],
                    ['fr' => 'Briefing sécurité', 'en' => 'Safety briefing'],
                ],
                'not_included' => [
                    ['fr' => 'Photos (15€ en supplément)', 'en' => 'Photos (€15 extra)'],
                ],
                'requirements' => [
                    ['fr' => 'Serviette', 'en' => 'Towel'],
                    ['fr' => 'Maillot de bain', 'en' => 'Swimsuit'],
                    ['fr' => 'Savoir nager', 'en' => 'Must know how to swim'],
                ],
                'safety_info' => [
                    'minimum_age' => 12,
                    'not_suitable_for' => [
                        ['fr' => 'Femmes enceintes', 'en' => 'Pregnant women'],
                    ],
                ],
                'meeting_point' => [
                    'address' => 'Neptune Watersport, Djerba Midun',
                    'latitude' => 33.8363181,
                    'longitude' => 11.0092221,
                    'instructions' => [
                        'fr' => 'Rendez-vous chez Neptune Watersport',
                        'en' => 'Meet at Neptune Watersport',
                    ],
                ],
                'pricing' => [
                    'person_types' => [
                        [
                            'key' => 'adult',
                            'label' => ['fr' => 'Par personne', 'en' => 'Per person'],
                            'tnd_price' => 45,
                            'eur_price' => 15,
                        ],
                    ],
                ],
                'duration' => ['value' => 8, 'unit' => 'minutes'],
                'difficulty' => 'easy',
                'min_group_size' => 1,
                'max_group_size' => 8,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/ban2.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/ban.jpg',
                    'https://www.evasiondjerba.com/uploads/0000/27/2025/11/13/banaa.jpg',
                ],
            ],
        ];

        foreach ($nautical as $data) {
            // Skip if listing with this slug already exists
            if (Listing::where('slug', $data['slug'])->exists()) {
                $this->command->warn("Listing '{$data['slug']}' already exists, skipping.");
                continue;
            }

            $listingData = [
                'vendor_id' => $vendor->id,
                'location_id' => $djerba->id,
                'activity_type_id' => $data['activity_type']?->id,
                'service_type' => $data['service_type'],
                'status' => ListingStatus::DRAFT,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'highlights' => $data['highlights'],
                'included' => $data['included'],
                'not_included' => $data['not_included'],
                'requirements' => $data['requirements'],
                'meeting_point' => $data['meeting_point'],
                'pricing' => $data['pricing'],
                'cancellation_policy' => [
                    'type' => 'flexible',
                    'rules' => [
                        ['hours_before_start' => 24, 'refund_percent' => 100],
                    ],
                    'description' => [
                        'fr' => 'Annulation gratuite jusqu\'à 24 heures avant',
                        'en' => 'Free cancellation up to 24 hours before',
                    ],
                ],
                'min_group_size' => $data['min_group_size'],
                'max_group_size' => $data['max_group_size'],
                'duration' => $data['duration'],
                'difficulty' => $data['difficulty'] ?? 'easy',
                'min_advance_booking_hours' => 24,
            ];

            if (isset($data['safety_info'])) {
                $listingData['safety_info'] = $data['safety_info'];
            }

            $listing = Listing::create($listingData);

            // Add media (first image = hero, rest = gallery)
            foreach ($data['images'] as $index => $imageUrl) {
                Media::create([
                    'mediable_type' => Listing::class,
                    'mediable_id' => $listing->id,
                    'url' => $imageUrl,
                    'alt' => $data['title']['fr'],
                    'type' => 'image',
                    'category' => $index === 0 ? MediaCategory::HERO : MediaCategory::GALLERY,
                    'order' => $index,
                ]);
            }

            // Add availability rule: all week, for next 180 days
            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(180),
                'capacity' => $data['max_group_size'],
                'is_active' => true,
            ]);

            $this->command->info("Created: {$data['title']['fr']}");
        }

        $this->command->info('7 nautical sports listings created as DRAFT.');
    }
}
