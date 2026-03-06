<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ServiceType;
use App\Enums\TagType;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [
            // ==================== Tour Types ====================
            [
                'type' => TagType::TOUR_TYPE,
                'name' => ['en' => 'Wildlife', 'fr' => 'Faune'],
                'slug' => 'wildlife',
                'description' => ['en' => 'Wildlife observation tours', 'fr' => 'Tours d\'observation de la faune'],
                'icon' => 'heroicon-o-eye',
                'color' => '#059669',
                'applicable_service_types' => [ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::TOUR_TYPE,
                'name' => ['en' => 'Adventure', 'fr' => 'Aventure'],
                'slug' => 'adventure',
                'description' => ['en' => 'Adventure and adrenaline tours', 'fr' => 'Tours d\'aventure et d\'adrénaline'],
                'icon' => 'heroicon-o-bolt',
                'color' => '#DC2626',
                'applicable_service_types' => [ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::TOUR_TYPE,
                'name' => ['en' => 'City', 'fr' => 'Ville'],
                'slug' => 'city-tour',
                'description' => ['en' => 'City exploration tours', 'fr' => 'Tours de découverte de la ville'],
                'icon' => 'heroicon-o-building-office-2',
                'color' => '#4F46E5',
                'applicable_service_types' => [ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::TOUR_TYPE,
                'name' => ['en' => 'Museum', 'fr' => 'Musée'],
                'slug' => 'museum',
                'description' => ['en' => 'Museum and cultural tours', 'fr' => 'Visites de musées et culturelles'],
                'icon' => 'heroicon-o-building-library',
                'color' => '#7C3AED',
                'applicable_service_types' => [ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::TOUR_TYPE,
                'name' => ['en' => 'Beach', 'fr' => 'Plage'],
                'slug' => 'beach-tour',
                'description' => ['en' => 'Beach and coastal tours', 'fr' => 'Tours de plage et côtiers'],
                'icon' => 'heroicon-o-sun',
                'color' => '#0891B2',
                'applicable_service_types' => [ServiceType::TOUR->value],
            ],

            // ==================== Boat Types ====================
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Jetboat', 'fr' => 'Jetboat'],
                'slug' => 'jetboat',
                'description' => ['en' => 'High-speed jet boat rides', 'fr' => 'Promenades en jetboat haute vitesse'],
                'icon' => 'heroicon-o-rocket-launch',
                'color' => '#0077B6',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Cabin Cruiser', 'fr' => 'Cabin Cruiser'],
                'slug' => 'cabin-cruiser',
                'description' => ['en' => 'Comfortable cabin cruiser boats', 'fr' => 'Bateaux cabin cruiser confortables'],
                'icon' => 'heroicon-o-home',
                'color' => '#0096C7',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Airboat', 'fr' => 'Aéroglisseur'],
                'slug' => 'airboat',
                'description' => ['en' => 'Airboat adventures', 'fr' => 'Aventures en aéroglisseur'],
                'icon' => 'heroicon-o-paper-airplane',
                'color' => '#00B4D8',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Electric', 'fr' => 'Électrique'],
                'slug' => 'electric-boat',
                'description' => ['en' => 'Eco-friendly electric boats', 'fr' => 'Bateaux électriques écologiques'],
                'icon' => 'heroicon-o-bolt',
                'color' => '#48CAE4',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Jet Ski', 'fr' => 'Jet Ski'],
                'slug' => 'jet-ski',
                'description' => ['en' => 'Personal watercraft experiences', 'fr' => 'Expériences en scooter des mers'],
                'icon' => 'heroicon-o-fire',
                'color' => '#EF4444',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Parasailing', 'fr' => 'Parachute Ascensionnel'],
                'slug' => 'parasailing',
                'description' => ['en' => 'Parasailing over the sea', 'fr' => 'Parachute ascensionnel au-dessus de la mer'],
                'icon' => 'heroicon-o-cloud',
                'color' => '#3B82F6',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],
            [
                'type' => TagType::BOAT_TYPE,
                'name' => ['en' => 'Diving', 'fr' => 'Plongée'],
                'slug' => 'diving',
                'description' => ['en' => 'Scuba diving and snorkeling', 'fr' => 'Plongée sous-marine et snorkeling'],
                'icon' => 'heroicon-o-lifebuoy',
                'color' => '#0E7490',
                'applicable_service_types' => [ServiceType::NAUTICAL->value],
            ],

            // ==================== Space Types ====================
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Villa', 'fr' => 'Villa'],
                'slug' => 'villa',
                'description' => ['en' => 'Luxury villas', 'fr' => 'Villas de luxe'],
                'icon' => 'heroicon-o-home-modern',
                'color' => '#F4A261',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Auditorium', 'fr' => 'Auditorium'],
                'slug' => 'auditorium',
                'description' => ['en' => 'Event auditoriums', 'fr' => 'Auditoriums pour événements'],
                'icon' => 'heroicon-o-presentation-chart-bar',
                'color' => '#E76F51',
                'applicable_service_types' => [ServiceType::EVENT->value],
            ],
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Cafe', 'fr' => 'Café'],
                'slug' => 'cafe',
                'description' => ['en' => 'Cozy cafes', 'fr' => 'Cafés confortables'],
                'icon' => 'heroicon-o-building-storefront',
                'color' => '#8B4513',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value, ServiceType::EVENT->value],
            ],
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Ballroom', 'fr' => 'Salle de bal'],
                'slug' => 'ballroom',
                'description' => ['en' => 'Elegant ballrooms', 'fr' => 'Salles de bal élégantes'],
                'icon' => 'heroicon-o-sparkles',
                'color' => '#D4AF37',
                'applicable_service_types' => [ServiceType::EVENT->value],
            ],
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Yoga Studio', 'fr' => 'Studio de yoga'],
                'slug' => 'yoga-studio',
                'description' => ['en' => 'Peaceful yoga studios', 'fr' => 'Studios de yoga paisibles'],
                'icon' => 'heroicon-o-heart',
                'color' => '#9F7AEA',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value, ServiceType::EVENT->value],
            ],
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Warehouse', 'fr' => 'Entrepôt'],
                'slug' => 'warehouse',
                'description' => ['en' => 'Industrial warehouse spaces', 'fr' => 'Espaces d\'entrepôt industriels'],
                'icon' => 'heroicon-o-cube',
                'color' => '#6B7280',
                'applicable_service_types' => [ServiceType::EVENT->value],
            ],
            [
                'type' => TagType::SPACE_TYPE,
                'name' => ['en' => 'Dance Studio', 'fr' => 'Studio de danse'],
                'slug' => 'dance-studio',
                'description' => ['en' => 'Dance and rehearsal studios', 'fr' => 'Studios de danse et de répétition'],
                'icon' => 'heroicon-o-musical-note',
                'color' => '#EC4899',
                'applicable_service_types' => [ServiceType::EVENT->value],
            ],

            // ==================== Event Features ====================
            [
                'type' => TagType::EVENT_FEATURE,
                'name' => ['en' => 'Camping', 'fr' => 'Camping'],
                'slug' => 'camping',
                'description' => ['en' => 'Camping facilities available', 'fr' => 'Installations de camping disponibles'],
                'icon' => 'heroicon-o-fire',
                'color' => '#15803D',
                'applicable_service_types' => [ServiceType::EVENT->value, ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::EVENT_FEATURE,
                'name' => ['en' => 'Trekking', 'fr' => 'Randonnée'],
                'slug' => 'trekking',
                'description' => ['en' => 'Trekking and hiking activities', 'fr' => 'Activités de randonnée'],
                'icon' => 'heroicon-o-map',
                'color' => '#854D0E',
                'applicable_service_types' => [ServiceType::EVENT->value, ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::EVENT_FEATURE,
                'name' => ['en' => 'Campfire', 'fr' => 'Feu de camp'],
                'slug' => 'campfire',
                'description' => ['en' => 'Campfire experiences', 'fr' => 'Expériences autour du feu de camp'],
                'icon' => 'heroicon-o-fire',
                'color' => '#EA580C',
                'applicable_service_types' => [ServiceType::EVENT->value, ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::EVENT_FEATURE,
                'name' => ['en' => 'Off Road', 'fr' => 'Tout-terrain'],
                'slug' => 'off-road',
                'description' => ['en' => 'Off-road adventures', 'fr' => 'Aventures tout-terrain'],
                'icon' => 'heroicon-o-truck',
                'color' => '#78716C',
                'applicable_service_types' => [ServiceType::EVENT->value, ServiceType::TOUR->value],
            ],
            [
                'type' => TagType::EVENT_FEATURE,
                'name' => ['en' => 'Exploring', 'fr' => 'Exploration'],
                'slug' => 'exploring',
                'description' => ['en' => 'Exploration activities', 'fr' => 'Activités d\'exploration'],
                'icon' => 'heroicon-o-globe-alt',
                'color' => '#2563EB',
                'applicable_service_types' => [ServiceType::EVENT->value, ServiceType::TOUR->value],
            ],

            // ==================== Amenities ====================
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'WiFi', 'fr' => 'WiFi'],
                'slug' => 'wifi',
                'description' => ['en' => 'Free WiFi available', 'fr' => 'WiFi gratuit disponible'],
                'icon' => 'heroicon-o-wifi',
                'color' => '#3B82F6',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'Pool', 'fr' => 'Piscine'],
                'slug' => 'pool',
                'description' => ['en' => 'Swimming pool', 'fr' => 'Piscine'],
                'icon' => 'heroicon-o-sparkles',
                'color' => '#06B6D4',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'AC', 'fr' => 'Climatisation'],
                'slug' => 'ac',
                'description' => ['en' => 'Air conditioning', 'fr' => 'Climatisation'],
                'icon' => 'heroicon-o-sun',
                'color' => '#0EA5E9',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'Kitchen', 'fr' => 'Cuisine'],
                'slug' => 'kitchen',
                'description' => ['en' => 'Fully equipped kitchen', 'fr' => 'Cuisine entièrement équipée'],
                'icon' => 'heroicon-o-fire',
                'color' => '#F97316',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'Breakfast', 'fr' => 'Petit-déjeuner'],
                'slug' => 'breakfast',
                'description' => ['en' => 'Breakfast included', 'fr' => 'Petit-déjeuner inclus'],
                'icon' => 'heroicon-o-cake',
                'color' => '#FBBF24',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'Parking', 'fr' => 'Parking'],
                'slug' => 'parking',
                'description' => ['en' => 'Free parking available', 'fr' => 'Parking gratuit disponible'],
                'icon' => 'heroicon-o-truck',
                'color' => '#6B7280',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'Gym', 'fr' => 'Salle de sport'],
                'slug' => 'gym',
                'description' => ['en' => 'Fitness center', 'fr' => 'Centre de fitness'],
                'icon' => 'heroicon-o-heart',
                'color' => '#EF4444',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value],
            ],
            [
                'type' => TagType::AMENITY,
                'name' => ['en' => 'Hot Tub', 'fr' => 'Jacuzzi'],
                'slug' => 'hot-tub',
                'description' => ['en' => 'Hot tub or jacuzzi', 'fr' => 'Bain à remous ou jacuzzi'],
                'icon' => 'heroicon-o-beaker',
                'color' => '#8B5CF6',
                'applicable_service_types' => [ServiceType::ACCOMMODATION->value, ServiceType::NAUTICAL->value],
            ],
        ];

        foreach ($tags as $index => $tag) {
            Tag::updateOrCreate(
                ['slug' => $tag['slug']],
                [
                    'uuid' => (string) Str::uuid(),
                    'type' => $tag['type'],
                    'name' => $tag['name'],
                    'description' => $tag['description'] ?? null,
                    'icon' => $tag['icon'],
                    'color' => $tag['color'],
                    'display_order' => $index,
                    'is_active' => true,
                    'listings_count' => 0,
                    'applicable_service_types' => $tag['applicable_service_types'],
                ]
            );
        }

        $this->command->info('Tags seeded successfully! Total: '.count($tags).' tags');
    }
}
