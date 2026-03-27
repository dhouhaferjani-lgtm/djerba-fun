<?php

namespace Database\Seeders;

use App\Enums\AvailabilityRuleType;
use App\Enums\ListingStatus;
use App\Enums\MediaCategory;
use App\Enums\ServiceType;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AccommodationSeeder extends Seeder
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

        $accommodations = [
            // 1. LUXURY SINGLE
            [
                'title' => [
                    'fr' => 'Villa de Luxe - Djerba',
                    'en' => 'Luxury Villa - Djerba',
                ],
                'slug' => 'villa-luxe-djerba',
                'summary' => [
                    'fr' => 'Magnifique villa de luxe avec 9 chambres, piscine et tous les équipements modernes pour un séjour inoubliable à Djerba.',
                    'en' => 'Magnificent luxury villa with 9 bedrooms, pool and all modern amenities for an unforgettable stay in Djerba.',
                ],
                'description' => [
                    'fr' => 'Cette villa de luxe spacieuse offre un cadre exceptionnel pour vos vacances à Djerba. Avec ses 9 chambres et 9 salles de bain, elle est parfaite pour les grandes familles ou les groupes d\'amis. Profitez de la piscine privée, de la cuisine entièrement équipée, de la climatisation et du parking privé. Le Wi-Fi est disponible dans toute la propriété. Service de réception 24h/24.',
                    'en' => 'This spacious luxury villa offers an exceptional setting for your holiday in Djerba. With 9 bedrooms and 9 bathrooms, it is perfect for large families or groups of friends. Enjoy the private pool, fully equipped kitchen, air conditioning and private parking. Wi-Fi is available throughout the property. 24-hour reception service.',
                ],
                'accommodation_type' => 'villa',
                'bedrooms' => 9,
                'bathrooms' => 9,
                'max_guests' => 10,
                'property_size' => 132,
                'nightly_price_eur' => 350,
                'nightly_price_tnd' => 1050,
                'check_in_time' => '14:00',
                'check_out_time' => '12:00',
                'amenities' => ['wifi', 'pool', 'air_conditioning', 'kitchen', 'parking', 'breakfast'],
                'highlights' => [
                    ['fr' => 'Piscine privée', 'en' => 'Private pool'],
                    ['fr' => '9 chambres spacieuses', 'en' => '9 spacious bedrooms'],
                    ['fr' => 'Réception 24h/24', 'en' => '24-hour reception'],
                    ['fr' => 'Cuisine entièrement équipée', 'en' => 'Fully equipped kitchen'],
                ],
                'included' => [
                    ['fr' => 'Wi-Fi gratuit', 'en' => 'Free Wi-Fi'],
                    ['fr' => 'Piscine privée', 'en' => 'Private pool'],
                    ['fr' => 'Climatisation', 'en' => 'Air conditioning'],
                    ['fr' => 'Parking privé', 'en' => 'Private parking'],
                ],
                'not_included' => [
                    ['fr' => 'Petit-déjeuner (disponible en supplément)', 'en' => 'Breakfast (available as extra)'],
                    ['fr' => 'Service de ménage quotidien', 'en' => 'Daily cleaning service'],
                ],
                'house_rules' => [
                    'fr' => 'Pas de fêtes ni d\'événements. Respecter les voisins. Pas de fumée à l\'intérieur.',
                    'en' => 'No parties or events. Respect the neighbors. No smoking indoors.',
                ],
                'meeting_point' => [
                    'address' => 'Djerba, Tunisie',
                    'latitude' => 33.8076,
                    'longitude' => 10.8451,
                    'instructions' => [
                        'fr' => 'Les détails d\'accès seront envoyés après la réservation',
                        'en' => 'Access details will be sent after booking',
                    ],
                ],
                'minimum_nights' => 2,
                'images' => [
                    'https://www.evasiondjerba.com/uploads/gotrip/space/s-1.png',
                    'https://www.evasiondjerba.com/uploads/gotrip/space/s-2.png',
                    'https://www.evasiondjerba.com/uploads/gotrip/space/s-3.png',
                    'https://www.evasiondjerba.com/uploads/gotrip/space/s-4.png',
                    'https://www.evasiondjerba.com/uploads/gotrip/space/s-5.png',
                ],
            ],

            // 2. Studio Zyed
            [
                'title' => [
                    'fr' => 'Studio Zyed',
                    'en' => 'Studio Zyed',
                ],
                'slug' => 'studio-zyed-djerba',
                'summary' => [
                    'fr' => 'Studio confortable et bien équipé avec climatisation, cuisine et parking. Idéal pour un couple ou un petit groupe.',
                    'en' => 'Comfortable and well-equipped studio with air conditioning, kitchen and parking. Ideal for a couple or small group.',
                ],
                'description' => [
                    'fr' => 'Ce studio de 50m² offre tout le confort nécessaire pour un séjour agréable à Djerba. Climatisé et bien équipé avec une cuisine fonctionnelle, il dispose d\'un parking privé. Parfait pour un couple ou un petit groupe de 3 personnes souhaitant profiter de Djerba à un prix abordable.',
                    'en' => 'This 50m² studio offers all the comfort needed for a pleasant stay in Djerba. Air-conditioned and well-equipped with a functional kitchen, it has private parking. Perfect for a couple or a small group of 3 looking to enjoy Djerba at an affordable price.',
                ],
                'accommodation_type' => 'apartment',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'max_guests' => 3,
                'property_size' => 50,
                'nightly_price_eur' => 120,
                'nightly_price_tnd' => 360,
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
                'amenities' => ['air_conditioning', 'kitchen', 'parking'],
                'highlights' => [
                    ['fr' => 'Prix abordable', 'en' => 'Affordable price'],
                    ['fr' => 'Cuisine équipée', 'en' => 'Equipped kitchen'],
                    ['fr' => 'Climatisation', 'en' => 'Air conditioning'],
                    ['fr' => 'Parking privé', 'en' => 'Private parking'],
                ],
                'included' => [
                    ['fr' => 'Climatisation', 'en' => 'Air conditioning'],
                    ['fr' => 'Cuisine équipée', 'en' => 'Equipped kitchen'],
                    ['fr' => 'Parking', 'en' => 'Parking'],
                ],
                'not_included' => [
                    ['fr' => 'Wi-Fi', 'en' => 'Wi-Fi'],
                    ['fr' => 'Petit-déjeuner', 'en' => 'Breakfast'],
                ],
                'house_rules' => [
                    'fr' => 'Pas de fêtes. Pas de fumée à l\'intérieur. Respecter le calme après 22h.',
                    'en' => 'No parties. No smoking indoors. Respect quiet hours after 10pm.',
                ],
                'meeting_point' => [
                    'address' => 'Djerba, Tunisie',
                    'latitude' => 33.8076,
                    'longitude' => 10.8451,
                    'instructions' => [
                        'fr' => 'Les détails d\'accès seront envoyés après la réservation',
                        'en' => 'Access details will be sent after booking',
                    ],
                ],
                'minimum_nights' => 2,
                'images' => [],
            ],

            // 3. Villa Yasmin
            [
                'title' => [
                    'fr' => 'Villa Yasmin – Midoun, Djerba',
                    'en' => 'Villa Yasmin – Midoun, Djerba',
                ],
                'slug' => 'villa-yasmin-midoun-djerba',
                'summary' => [
                    'fr' => 'Villa charmante avec piscine privée, à quelques minutes des plages de Sidi Mahrez. Idéale pour des vacances en famille ou entre amis.',
                    'en' => 'Charming villa with private pool, located just a few minutes from the beaches of Sidi Mahrez. Ideal for holidays with family or friends.',
                ],
                'description' => [
                    'fr' => 'Villa charmante avec piscine privée, située à quelques minutes des plages de Sidi Mahrez. Idéale pour des vacances en famille ou entre amis, cette maison offre tout le confort moderne dans un cadre typiquement djerbien. Avec ses 4 chambres et 2 salles de bain, elle accueille confortablement jusqu\'à 10 personnes. Profitez de la climatisation, du Wi-Fi, de la cuisine entièrement équipée et du parking privé.',
                    'en' => 'Charming villa with private pool, located just a few minutes from the beaches of Sidi Mahrez. Ideal for holidays with family or friends, this house offers all modern comforts in a typically Djerbian setting. With 4 bedrooms and 2 bathrooms, it comfortably accommodates up to 10 people. Enjoy air conditioning, Wi-Fi, fully equipped kitchen and private parking.',
                ],
                'accommodation_type' => 'villa',
                'bedrooms' => 4,
                'bathrooms' => 2,
                'max_guests' => 10,
                'property_size' => 200,
                'nightly_price_eur' => 150,
                'nightly_price_tnd' => 450,
                'check_in_time' => '15:00',
                'check_out_time' => '11:00',
                'amenities' => ['wifi', 'pool', 'air_conditioning', 'kitchen', 'parking', 'breakfast'],
                'highlights' => [
                    ['fr' => 'Piscine privée', 'en' => 'Private pool'],
                    ['fr' => 'Proche des plages de Sidi Mahrez', 'en' => 'Close to Sidi Mahrez beaches'],
                    ['fr' => 'Cadre typiquement djerbien', 'en' => 'Typical Djerbian setting'],
                    ['fr' => '4 chambres, jusqu\'à 10 personnes', 'en' => '4 bedrooms, up to 10 guests'],
                ],
                'included' => [
                    ['fr' => 'Wi-Fi gratuit', 'en' => 'Free Wi-Fi'],
                    ['fr' => 'Piscine privée', 'en' => 'Private pool'],
                    ['fr' => 'Climatisation', 'en' => 'Air conditioning'],
                    ['fr' => 'Cuisine entièrement équipée', 'en' => 'Fully equipped kitchen'],
                    ['fr' => 'Parking privé', 'en' => 'Private parking'],
                ],
                'not_included' => [
                    ['fr' => 'Service de ménage quotidien', 'en' => 'Daily cleaning service'],
                    ['fr' => 'Transfert aéroport', 'en' => 'Airport transfer'],
                ],
                'house_rules' => [
                    'fr' => 'Pas de fêtes ni d\'événements. Respecter les voisins. Pas de fumée à l\'intérieur. Les animaux ne sont pas acceptés.',
                    'en' => 'No parties or events. Respect the neighbors. No smoking indoors. Pets are not allowed.',
                ],
                'meeting_point' => [
                    'address' => 'Midoun, Djerba, Tunisie',
                    'latitude' => 33.8081,
                    'longitude' => 10.9945,
                    'instructions' => [
                        'fr' => 'Les détails d\'accès seront envoyés après la réservation',
                        'en' => 'Access details will be sent after booking',
                    ],
                ],
                'minimum_nights' => 2,
                'images' => [],
            ],
        ];

        foreach ($accommodations as $data) {
            if (Listing::where('slug', $data['slug'])->exists()) {
                $this->command->warn("Listing '{$data['slug']}' already exists, skipping.");
                continue;
            }

            $listing = Listing::create([
                'vendor_id' => $vendor->id,
                'location_id' => $djerba->id,
                'service_type' => ServiceType::ACCOMMODATION,
                'status' => ListingStatus::PUBLISHED,
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'highlights' => $data['highlights'],
                'included' => $data['included'],
                'not_included' => $data['not_included'],
                'requirements' => [],
                'meeting_point' => $data['meeting_point'],
                'house_rules' => $data['house_rules'],
                'cancellation_policy' => [
                    'type' => 'moderate',
                    'rules' => [
                        ['hours_before_start' => 48, 'refund_percent' => 100],
                        ['hours_before_start' => 24, 'refund_percent' => 50],
                    ],
                    'description' => [
                        'fr' => 'Annulation gratuite jusqu\'à 48 heures avant. 50% de remboursement jusqu\'à 24 heures avant.',
                        'en' => 'Free cancellation up to 48 hours before. 50% refund up to 24 hours before.',
                    ],
                ],
                'accommodation_type' => $data['accommodation_type'],
                'bedrooms' => $data['bedrooms'],
                'bathrooms' => $data['bathrooms'],
                'max_guests' => $data['max_guests'],
                'property_size' => $data['property_size'],
                'nightly_price_eur' => $data['nightly_price_eur'],
                'nightly_price_tnd' => $data['nightly_price_tnd'],
                'check_in_time' => $data['check_in_time'],
                'check_out_time' => $data['check_out_time'],
                'amenities' => $data['amenities'],
                'minimum_nights' => $data['minimum_nights'],
                'min_group_size' => 1,
                'max_group_size' => $data['max_guests'],
                'min_advance_booking_hours' => 72,
            ]);

            // Add media (first = hero, rest = gallery)
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
            // Include start_time/end_time from listing's check-in/check-out times
            // This ensures CalculateAvailabilityJob creates slots with correct times
            AvailabilityRule::create([
                'listing_id' => $listing->id,
                'rule_type' => AvailabilityRuleType::WEEKLY,
                'days_of_week' => [0, 1, 2, 3, 4, 5, 6],
                'start_time' => Carbon::parse($data['check_in_time']),
                'end_time' => Carbon::parse($data['check_out_time']),
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addDays(180),
                'capacity' => 1,
                'is_active' => true,
            ]);

            $this->command->info("Created: {$data['title']['fr']}");
        }

        $this->command->info('3 accommodation listings created as PUBLISHED with availability rules.');
    }
}
