<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AvailabilityRuleType;
use App\Enums\DifficultyLevel;
use App\Enums\ListingStatus;
use App\Enums\MediaCategory;
use App\Enums\ServiceType;
use App\Enums\UserRole;
use App\Models\AvailabilityRule;
use App\Models\Listing;
use App\Models\ListingFaq;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;

class HikingTourWithMapSeeder extends Seeder
{
    /**
     * Create a hiking tour with full itinerary, elevation profile, and map data.
     */
    public function run(): void
    {
        $vendor = User::where('role', UserRole::VENDOR)->first();

        if (! $vendor) {
            $this->command->error('No vendor user found. Please seed users first.');

            return;
        }

        // Check if listing already exists
        $existingListing = Listing::where('slug', 'kroumirie-mountains-summit-trek')->first();

        if ($existingListing) {
            $this->command->info('ℹ️  Hiking tour already exists. Skipping creation.');
            $this->command->info('Listing slug: ' . $existingListing->slug);

            return;
        }

        // Get or create a location (using Ain Draham region - mountainous area in Tunisia)
        $location = Location::where('slug', 'ain-draham')->first();

        if (! $location) {
            $location = Location::create([
                'name' => [
                    'en' => 'Ain Draham',
                    'fr' => 'Ain Draham',
                ],
                'slug' => 'ain-draham',
                'country' => 'TN',
                'city' => 'Ain Draham',
                'region' => 'Jendouba',
                'latitude' => 36.7833,
                'longitude' => 8.6833,
                'timezone' => 'Africa/Tunis',
                'description' => [
                    'en' => 'A mountainous town in northwestern Tunisia, known for its forests and hiking trails.',
                    'fr' => 'Une ville montagneuse du nord-ouest de la Tunisie, connue pour ses forêts et sentiers de randonnée.',
                ],
                'image_url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800',
            ]);
        }

        // Create the hiking tour listing
        $listing = Listing::create([
            'vendor_id' => $vendor->id,
            'location_id' => $location->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
            'title' => [
                'en' => 'Kroumirie Mountains Summit Trek',
                'fr' => 'Trek au Sommet des Monts Kroumirie',
            ],
            'slug' => 'kroumirie-mountains-summit-trek',
            'summary' => [
                'en' => 'Challenge yourself with this spectacular mountain trek through Tunisia\'s lush Kroumirie forests. Experience stunning panoramic views, diverse wildlife, and ancient cork oak forests.',
                'fr' => 'Défiez-vous avec ce trek de montagne spectaculaire à travers les forêts luxuriantes de Kroumirie en Tunisie. Découvrez des vues panoramiques époustouflantes, une faune diversifiée et d\'anciennes forêts de chênes-lièges.',
            ],
            'description' => [
                'en' => "Embark on an unforgettable full-day mountain trek through the breathtaking Kroumirie Mountains. This challenging yet rewarding hike takes you through ancient cork oak forests, past cascading waterfalls, and up to stunning summit viewpoints.\n\nYour adventure begins at the trailhead in Ain Draham, where you'll meet your experienced mountain guide. As you ascend through dense forests, you'll encounter diverse flora and fauna unique to this region. The trail features several scenic viewpoints where you can catch your breath and capture spectacular photos.\n\nThe summit offers 360-degree views of the surrounding mountains, valleys, and on clear days, you can even see the Mediterranean Sea in the distance. After enjoying a well-deserved lunch at the peak, we'll descend via a different route, passing by traditional Berber villages and natural springs.\n\nThis trek is designed for adventurous hikers with good fitness levels. The total distance is approximately 12 kilometers with an elevation gain of 650 meters. Our guides are trained in wilderness first aid and carry emergency equipment for your safety.",
                'fr' => "Embarquez pour une randonnée de montagne inoubliable d'une journée complète à travers les monts Kroumirie à couper le souffle. Cette randonnée difficile mais gratifiante vous emmène à travers d'anciennes forêts de chênes-lièges, devant des cascades en cascade et jusqu'à de superbes points de vue au sommet.\n\nVotre aventure commence au début du sentier à Ain Draham, où vous rencontrerez votre guide de montagne expérimenté. En montant à travers des forêts denses, vous rencontrerez une flore et une faune diversifiées uniques à cette région. Le sentier comprend plusieurs points de vue panoramiques où vous pourrez reprendre votre souffle et capturer des photos spectaculaires.\n\nLe sommet offre une vue à 360 degrés sur les montagnes, les vallées environnantes et, par temps clair, vous pouvez même voir la mer Méditerranée au loin. Après avoir savouré un déjeuner bien mérité au sommet, nous descendrons par une route différente, en passant par des villages berbères traditionnels et des sources naturelles.",
            ],
            'highlights' => [
                [
                    'en' => 'Summit panoramic views of Kroumirie Mountains and Mediterranean Sea',
                    'fr' => 'Vues panoramiques du sommet des monts Kroumirie et de la mer Méditerranée',
                ],
                [
                    'en' => 'Trek through ancient cork oak forests teeming with wildlife',
                    'fr' => 'Randonnée à travers d\'anciennes forêts de chênes-lièges regorgeant de faune',
                ],
                [
                    'en' => 'Visit traditional Berber mountain villages',
                    'fr' => 'Visite de villages berbères de montagne traditionnels',
                ],
                [
                    'en' => 'Natural mountain springs and cascading waterfalls',
                    'fr' => 'Sources de montagne naturelles et cascades en cascade',
                ],
                [
                    'en' => 'Expert mountain guide with wilderness first aid training',
                    'fr' => 'Guide de montagne expert avec formation aux premiers secours en milieu sauvage',
                ],
            ],
            'included' => [
                [
                    'en' => 'Certified mountain guide',
                    'fr' => 'Guide de montagne certifié',
                ],
                [
                    'en' => 'Summit lunch pack (sandwich, fruits, energy bars, water)',
                    'fr' => 'Pack déjeuner au sommet (sandwich, fruits, barres énergétiques, eau)',
                ],
                [
                    'en' => 'Transportation from Ain Draham to trailhead and back',
                    'fr' => 'Transport depuis Ain Draham jusqu\'au début du sentier et retour',
                ],
                [
                    'en' => 'Trekking poles (if needed)',
                    'fr' => 'Bâtons de randonnée (si nécessaire)',
                ],
                [
                    'en' => 'First aid kit and emergency equipment',
                    'fr' => 'Trousse de premiers secours et équipement d\'urgence',
                ],
                [
                    'en' => 'Trail map and route briefing',
                    'fr' => 'Carte du sentier et briefing de l\'itinéraire',
                ],
            ],
            'not_included' => [
                [
                    'en' => 'Personal hiking equipment (boots, backpack, clothing)',
                    'fr' => 'Équipement de randonnée personnel (bottes, sac à dos, vêtements)',
                ],
                [
                    'en' => 'Travel insurance',
                    'fr' => 'Assurance voyage',
                ],
                [
                    'en' => 'Gratuities for guide (optional)',
                    'fr' => 'Pourboires pour le guide (facultatif)',
                ],
            ],
            'requirements' => [
                [
                    'en' => 'Good physical fitness - ability to hike 12km with 650m elevation gain',
                    'fr' => 'Bonne condition physique - capacité à randonner 12 km avec 650 m de dénivelé positif',
                ],
                [
                    'en' => 'Proper hiking boots with ankle support',
                    'fr' => 'Bottes de randonnée appropriées avec support de cheville',
                ],
                [
                    'en' => 'Weather-appropriate clothing (layers recommended)',
                    'fr' => 'Vêtements adaptés aux conditions météorologiques (couches recommandées)',
                ],
                [
                    'en' => 'Minimum 2 liters of water per person',
                    'fr' => 'Minimum 2 litres d\'eau par personne',
                ],
                [
                    'en' => 'Sun protection (hat, sunglasses, sunscreen)',
                    'fr' => 'Protection solaire (chapeau, lunettes de soleil, crème solaire)',
                ],
            ],
            'meeting_point' => [
                'address' => 'Ain Draham Tourism Office, Avenue Habib Bourguiba, Ain Draham 8110, Tunisia',
                'coordinates' => [
                    'latitude' => 36.7833,
                    'longitude' => 8.6833,
                ],
                'instructions' => [
                    'en' => 'Meet at the Ain Draham Tourism Office at 7:00 AM. Look for our guide with a "Djerba Fun" sign. Free parking available. Please arrive 15 minutes early for equipment check and safety briefing.',
                    'fr' => 'Rendez-vous au Bureau du Tourisme d\'Ain Draham à 7h00. Cherchez notre guide avec un panneau "Djerba Fun". Parking gratuit disponible. Veuillez arriver 15 minutes à l\'avance pour la vérification de l\'équipement et le briefing de sécurité.',
                ],
            ],
            'pricing' => [
                'tnd_price' => 120,
                'eur_price' => 38,
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => [
                            'en' => 'Adult',
                            'fr' => 'Adulte',
                        ],
                        'tnd_price' => 120,
                        'eur_price' => 38,
                        'min_age' => 16,
                        'max_age' => null,
                        'min_quantity' => 1,
                        'max_quantity' => null,
                    ],
                ],
                'group_discount' => [
                    'min_size' => 4,
                    'discount_percent' => 15,
                ],
            ],
            'cancellation_policy' => [
                'type' => 'moderate',
                'rules' => [
                    [
                        'hours_before_start' => 48,
                        'refund_percent' => 100,
                    ],
                    [
                        'hours_before_start' => 24,
                        'refund_percent' => 50,
                    ],
                    [
                        'hours_before_start' => 0,
                        'refund_percent' => 0,
                    ],
                ],
                'description' => [
                    'en' => 'Free cancellation up to 48 hours before start. Cancel between 24-48 hours for 50% refund. No refund for cancellations within 24 hours.',
                    'fr' => 'Annulation gratuite jusqu\'à 48 heures avant le départ. Annulation entre 24 et 48 heures pour un remboursement de 50%. Aucun remboursement pour les annulations dans les 24 heures.',
                ],
            ],
            'min_group_size' => 2,
            'max_group_size' => 8,
            'duration' => [
                'value' => 9,
                'unit' => 'hours',
            ],
            'difficulty' => DifficultyLevel::CHALLENGING,
            'distance' => 12, // kilometers
            'rating' => 4.9,
            'reviews_count' => 78,
            'bookings_count' => 156,
            'published_at' => now(),

            // Itinerary with coordinates and elevation
            'itinerary' => [
                [
                    'order' => 0,
                    'title' => [
                        'en' => 'Trailhead - Ain Draham Forest Entrance',
                        'fr' => 'Début du sentier - Entrée de la forêt d\'Ain Draham',
                    ],
                    'description' => [
                        'en' => 'Begin your journey at the forest entrance. Gear check, safety briefing, and trail orientation.',
                        'fr' => 'Commencez votre voyage à l\'entrée de la forêt. Vérification de l\'équipement, briefing de sécurité et orientation du sentier.',
                    ],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.7850,
                        'lng' => 8.6900,
                        'elevation' => 800,
                    ],
                ],
                [
                    'order' => 1,
                    'title' => [
                        'en' => 'Cork Oak Forest Trail',
                        'fr' => 'Sentier de la forêt de chênes-lièges',
                    ],
                    'description' => [
                        'en' => 'Trek through ancient cork oak trees. Watch for wild boar, jackals, and various bird species. The forest canopy provides welcome shade.',
                        'fr' => 'Trek à travers des chênes-lièges centenaires. Surveillez les sangliers, les chacals et diverses espèces d\'oiseaux. La canopée forestière offre une ombre bienvenue.',
                    ],
                    'duration' => 90,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.7920,
                        'lng' => 8.6950,
                        'elevation' => 950,
                    ],
                ],
                [
                    'order' => 2,
                    'title' => [
                        'en' => 'Waterfall Viewpoint',
                        'fr' => 'Point de vue sur la cascade',
                    ],
                    'description' => [
                        'en' => 'Rest stop at a scenic waterfall. Perfect spot for photos and water refill from the natural spring.',
                        'fr' => 'Arrêt de repos à une cascade pittoresque. Endroit parfait pour les photos et le remplissage d\'eau de la source naturelle.',
                    ],
                    'duration' => 20,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.8000,
                        'lng' => 8.7020,
                        'elevation' => 1050,
                    ],
                ],
                [
                    'order' => 3,
                    'title' => [
                        'en' => 'Ridge Trail - Mountain Slope',
                        'fr' => 'Sentier de crête - Pente de montagne',
                    ],
                    'description' => [
                        'en' => 'Steeper section with switchbacks. The trail opens up with stunning valley views. Take your time and pace yourself.',
                        'fr' => 'Section plus raide avec des lacets. Le sentier s\'ouvre sur de superbes vues sur la vallée. Prenez votre temps et adaptez votre rythme.',
                    ],
                    'duration' => 120,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.8080,
                        'lng' => 8.7100,
                        'elevation' => 1250,
                    ],
                ],
                [
                    'order' => 4,
                    'title' => [
                        'en' => 'Berber Village Overlook',
                        'fr' => 'Vue sur le village berbère',
                    ],
                    'description' => [
                        'en' => 'Panoramic viewpoint overlooking traditional Berber mountain villages. Brief cultural insights from your guide about mountain life.',
                        'fr' => 'Point de vue panoramique sur les villages berbères de montagne traditionnels. Brefs aperçus culturels de votre guide sur la vie en montagne.',
                    ],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.8150,
                        'lng' => 8.7150,
                        'elevation' => 1350,
                    ],
                ],
                [
                    'order' => 5,
                    'title' => [
                        'en' => 'Summit - Jebel Bir Peak',
                        'fr' => 'Sommet - Pic Jebel Bir',
                    ],
                    'description' => [
                        'en' => 'You made it! Celebrate at the summit with 360° views. On clear days, see the Mediterranean Sea. Lunch break and photo opportunities.',
                        'fr' => 'Vous avez réussi! Célébrez au sommet avec des vues à 360°. Par temps clair, voyez la mer Méditerranée. Pause déjeuner et opportunités de photos.',
                    ],
                    'duration' => 60,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.8200,
                        'lng' => 8.7200,
                        'elevation' => 1450,
                    ],
                ],
                [
                    'order' => 6,
                    'title' => [
                        'en' => 'Descent - Eastern Trail',
                        'fr' => 'Descente - Sentier Est',
                    ],
                    'description' => [
                        'en' => 'Gradual descent through pine forests. Different perspectives and ecosystems than the ascent route.',
                        'fr' => 'Descente progressive à travers des forêts de pins. Perspectives et écosystèmes différents de la route d\'ascension.',
                    ],
                    'duration' => 90,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.8150,
                        'lng' => 8.7280,
                        'elevation' => 1200,
                    ],
                ],
                [
                    'order' => 7,
                    'title' => [
                        'en' => 'Mountain Spring Rest Stop',
                        'fr' => 'Arrêt de repos à la source de montagne',
                    ],
                    'description' => [
                        'en' => 'Cool, refreshing spring water. Last rest stop before final descent.',
                        'fr' => 'Eau de source fraîche et rafraîchissante. Dernier arrêt de repos avant la descente finale.',
                    ],
                    'duration' => 15,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.8100,
                        'lng' => 8.7350,
                        'elevation' => 950,
                    ],
                ],
                [
                    'order' => 8,
                    'title' => [
                        'en' => 'Return to Trailhead',
                        'fr' => 'Retour au début du sentier',
                    ],
                    'description' => [
                        'en' => 'Complete your mountain adventure. Celebration and feedback with your guide. Transportation back to Ain Draham.',
                        'fr' => 'Terminez votre aventure en montagne. Célébration et retour d\'information avec votre guide. Transport de retour vers Ain Draham.',
                    ],
                    'duration' => 30,
                    'locationId' => null,
                    'coordinates' => [
                        'lat' => 36.7850,
                        'lng' => 8.6900,
                        'elevation' => 800,
                    ],
                ],
            ],
            'has_elevation_profile' => true,

            'safety_info' => [
                'required_fitness_level' => [
                    'en' => 'Good physical fitness - ability to hike continuously for 8+ hours with significant elevation gain',
                    'fr' => 'Bonne condition physique - capacité à randonner en continu pendant plus de 8 heures avec un dénivelé important',
                ],
                'minimum_age' => 16,
                'maximum_age' => null,
                'insurance_required' => true,
                'not_suitable_for' => [
                    [
                        'en' => 'Pregnant women',
                        'fr' => 'Femmes enceintes',
                    ],
                    [
                        'en' => 'People with heart conditions or respiratory issues',
                        'fr' => 'Personnes souffrant de problèmes cardiaques ou respiratoires',
                    ],
                    [
                        'en' => 'Those with severe knee or ankle problems',
                        'fr' => 'Personnes ayant de graves problèmes de genou ou de cheville',
                    ],
                    [
                        'en' => 'Anyone with fear of heights',
                        'fr' => 'Toute personne ayant peur des hauteurs',
                    ],
                ],
                'safety_equipment_provided' => [
                    [
                        'en' => 'First aid kit with wilderness medical supplies',
                        'fr' => 'Trousse de premiers secours avec fournitures médicales de montagne',
                    ],
                    [
                        'en' => 'Emergency communication device (satellite phone)',
                        'fr' => 'Appareil de communication d\'urgence (téléphone satellite)',
                    ],
                    [
                        'en' => 'Emergency shelter and thermal blankets',
                        'fr' => 'Abri d\'urgence et couvertures thermiques',
                    ],
                ],
            ],
            'accessibility_info' => [
                'wheelchair_accessible' => false,
                'mobility_aid_accessible' => false,
                'accessible_parking' => true,
                'accessible_restrooms' => false,
                'service_animals_allowed' => false,
                'accessibility_notes' => [
                    'en' => 'This is a challenging mountain trek on natural terrain with significant elevation gain. Not suitable for wheelchairs or mobility aids. Requires good physical fitness and proper hiking equipment.',
                    'fr' => 'Il s\'agit d\'un trek de montagne difficile sur terrain naturel avec un dénivelé important. Non adapté aux fauteuils roulants ou aux aides à la mobilité. Nécessite une bonne condition physique et un équipement de randonnée approprié.',
                ],
            ],
            'difficulty_details' => [
                'description' => [
                    'en' => 'Challenging full-day mountain trek with sustained elevation gain',
                    'fr' => 'Trek de montagne difficile d\'une journée complète avec dénivelé soutenu',
                ],
                'terrain_type' => [
                    'en' => 'Mountain trails, forest paths, rocky sections, steep switchbacks',
                    'fr' => 'Sentiers de montagne, chemins forestiers, sections rocheuses, lacets raides',
                ],
                'elevation_gain_meters' => 650,
                'technical_difficulty' => [
                    'en' => 'Intermediate - some scrambling required, sure footing essential',
                    'fr' => 'Intermédiaire - quelques passages nécessitant l\'escalade, pied sûr essentiel',
                ],
                'physical_intensity' => [
                    'en' => 'High - 8-9 hours of continuous hiking with significant elevation',
                    'fr' => 'Élevée - 8-9 heures de randonnée continue avec dénivelé important',
                ],
            ],
        ]);

        // Create FAQs
        $faqs = [
            [
                'question' => [
                    'en' => 'What is the best time of year for this trek?',
                    'fr' => 'Quelle est la meilleure période de l\'année pour ce trek ?',
                ],
                'answer' => [
                    'en' => 'The best months are March-May and September-November when temperatures are moderate (15-25°C). Summer can be very hot, and winter may have snow at higher elevations. We operate year-round but adjust routes based on conditions.',
                    'fr' => 'Les meilleurs mois sont mars-mai et septembre-novembre lorsque les températures sont modérées (15-25°C). L\'été peut être très chaud et l\'hiver peut avoir de la neige à des altitudes plus élevées. Nous opérons toute l\'année mais ajustons les itinéraires en fonction des conditions.',
                ],
                'order' => 0,
            ],
            [
                'question' => [
                    'en' => 'Do I need hiking experience?',
                    'fr' => 'Ai-je besoin d\'expérience en randonnée ?',
                ],
                'answer' => [
                    'en' => 'Yes, prior hiking experience is recommended. You should be comfortable hiking for 8+ hours and have experience with mountain trails. If you\'re new to hiking, consider starting with our easier day hikes first.',
                    'fr' => 'Oui, une expérience préalable en randonnée est recommandée. Vous devriez être à l\'aise pour randonner pendant plus de 8 heures et avoir de l\'expérience avec les sentiers de montagne. Si vous êtes nouveau à la randonnée, envisagez de commencer par nos randonnées d\'une journée plus faciles.',
                ],
                'order' => 1,
            ],
            [
                'question' => [
                    'en' => 'What should I bring?',
                    'fr' => 'Que dois-je apporter ?',
                ],
                'answer' => [
                    'en' => 'Essential items: sturdy hiking boots, backpack (20-30L), 2+ liters of water, layered clothing, rain jacket, sun protection, personal medications, snacks. Optional: camera, binoculars, trekking poles (we can provide). Full packing list sent upon booking.',
                    'fr' => 'Articles essentiels : bottes de randonnée robustes, sac à dos (20-30L), 2+ litres d\'eau, vêtements en couches, veste de pluie, protection solaire, médicaments personnels, collations. Facultatif : appareil photo, jumelles, bâtons de randonnée (nous pouvons fournir). Liste complète de bagages envoyée lors de la réservation.',
                ],
                'order' => 2,
            ],
            [
                'question' => [
                    'en' => 'What if weather conditions are bad?',
                    'fr' => 'Que se passe-t-il si les conditions météorologiques sont mauvaises ?',
                ],
                'answer' => [
                    'en' => 'Safety is our top priority. If conditions are unsafe (storms, high winds, poor visibility), we\'ll reschedule for another date or offer a full refund. We monitor weather forecasts closely and will notify you 24-48 hours before the trek if changes are needed.',
                    'fr' => 'La sécurité est notre priorité absolue. Si les conditions sont dangereuses (tempêtes, vents forts, mauvaise visibilité), nous reprogrammerons pour une autre date ou offrirons un remboursement complet. Nous surveillons de près les prévisions météorologiques et vous informerons 24 à 48 heures avant le trek si des changements sont nécessaires.',
                ],
                'order' => 3,
            ],
        ];

        foreach ($faqs as $faq) {
            ListingFaq::create([
                'listing_id' => $listing->id,
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'order' => $faq['order'],
                'is_active' => true,
            ]);
        }

        // Create media
        $mediaItems = [
            [
                'url' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=1200',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1551632811-561732d1e306?w=400',
                'alt' => 'Mountain summit view with hiker',
                'type' => 'image',
                'category' => MediaCategory::HERO,
                'order' => 0,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=1200',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=400',
                'alt' => 'Mountain trail through forest',
                'type' => 'image',
                'category' => MediaCategory::FEATURED,
                'order' => 1,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=1200',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=400',
                'alt' => 'Panoramic mountain vista',
                'type' => 'image',
                'category' => MediaCategory::FEATURED,
                'order' => 2,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1519904981063-b0cf448d479e?w=1200',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1519904981063-b0cf448d479e?w=400',
                'alt' => 'Waterfall along mountain trail',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 3,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?w=1200',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1486870591958-9b9d0d1dda99?w=400',
                'alt' => 'Mountain landscape with clouds',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 4,
            ],
        ];

        foreach ($mediaItems as $media) {
            Media::create([
                'mediable_type' => Listing::class,
                'mediable_id' => $listing->id,
                'url' => $media['url'],
                'thumbnail_url' => $media['thumbnail_url'],
                'alt' => $media['alt'],
                'type' => $media['type'],
                'category' => $media['category'],
                'order' => $media['order'],
            ]);
        }

        // Create availability rules for the next 3 months
        // Weekends (Saturday & Sunday)
        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [6, 0], // Saturday and Sunday
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'start_time' => '07:00:00',
            'end_time' => '16:00:00',
            'capacity' => 8,
            'is_active' => true,
        ]);

        // Additional mid-week trek on Wednesdays
        AvailabilityRule::create([
            'listing_id' => $listing->id,
            'rule_type' => AvailabilityRuleType::WEEKLY,
            'days_of_week' => [3], // Wednesday
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
            'start_time' => '07:00:00',
            'end_time' => '16:00:00',
            'capacity' => 8,
            'is_active' => true,
        ]);

        $this->command->info('✅ Hiking tour with full map data created successfully!');
        $this->command->info('Listing slug: ' . $listing->slug);
        $this->command->info('Itinerary stops: ' . count($listing->itinerary));
        $this->command->info('Has elevation profile: ' . ($listing->has_elevation_profile ? 'Yes' : 'No'));
        $this->command->info('Media items: ' . count($mediaItems));
        $this->command->info('FAQs: ' . count($faqs));
        $this->command->info('Availability rules created for next 3 months');
    }
}
