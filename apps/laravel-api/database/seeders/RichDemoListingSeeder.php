<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\DifficultyLevel;
use App\Enums\MediaCategory;
use App\Enums\ServiceType;
use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\ListingFaq;
use App\Models\Location;
use App\Models\User;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RichDemoListingSeeder extends Seeder
{
    /**
     * Create a fully-featured demo listing with all bells and whistles.
     */
    public function run(): void
    {
        $vendor = User::where('role', UserRole::VENDOR)->first();
        if (!$vendor) {
            $this->command->error('No vendor user found. Please seed users first.');
            return;
        }

        $location = Location::first();
        if (!$location) {
            $this->command->error('No location found. Please seed locations first.');
            return;
        }

        // Create a rich tour listing
        $listing = Listing::create([
            'vendor_id' => $vendor->id,
            'location_id' => $location->id,
            'service_type' => ServiceType::TOUR,
            'status' => ListingStatus::PUBLISHED,
            'title' => [
                'en' => 'Desert Safari & Sahara Sunset Experience',
                'fr' => 'Safari du Désert & Expérience du Coucher de Soleil au Sahara',
            ],
            'slug' => 'desert-safari-sahara-sunset',
            'summary' => [
                'en' => 'Embark on an unforgettable journey through the golden dunes of the Sahara. Experience traditional Berber culture, ride camels at sunset, and sleep under a blanket of stars.',
                'fr' => 'Embarquez pour un voyage inoubliable à travers les dunes dorées du Sahara. Découvrez la culture berbère traditionnelle, montez à dos de chameau au coucher du soleil et dormez sous un tapis d\'étoiles.',
            ],
            'description' => [
                'en' => "Discover the magic of Tunisia's Sahara Desert on this immersive full-day adventure. Your journey begins with a scenic drive through the dramatic landscape of Douz, known as the \"Gateway to the Sahara.\"\n\nAs we venture deeper into the desert, you'll witness the mesmerizing transition from rocky terrain to endless golden sand dunes. Our experienced Berber guides will share stories of desert life, ancient caravan routes, and the secrets of surviving in this harsh yet beautiful environment.\n\nThe highlight of your day comes as the sun begins its descent. You'll mount a gentle dromedary camel for a peaceful trek across the dunes, perfectly timed to witness one of nature's most spectacular shows – a Sahara sunset. The sky transforms into a canvas of oranges, purples, and reds as the sun dips below the horizon.\n\nAs darkness falls, we'll arrive at our traditional Berber camp where a warm welcome awaits. Enjoy authentic Tunisian cuisine around the campfire, traditional music under the stars, and fascinating conversations with local nomads who call this desert home.",
                'fr' => "Découvrez la magie du désert du Sahara tunisien lors de cette aventure immersive d'une journée complète. Votre voyage commence par un trajet pittoresque à travers le paysage spectaculaire de Douz, connue comme la \"Porte du Sahara\".\n\nÀ mesure que nous nous aventurons plus profondément dans le désert, vous serez témoin de la transition fascinante du terrain rocheux aux dunes de sable doré infinies. Nos guides berbères expérimentés partageront des histoires de la vie dans le désert, des anciennes routes caravanières et des secrets pour survivre dans cet environnement difficile mais magnifique.\n\nLe point culminant de votre journée arrive alors que le soleil commence sa descente. Vous monterez sur un dromadaire doux pour une randonnée paisible à travers les dunes, parfaitement chronométrée pour assister à l'un des spectacles les plus spectaculaires de la nature – un coucher de soleil au Sahara. Le ciel se transforme en une toile d'oranges, de pourpres et de rouges alors que le soleil plonge sous l'horizon.\n\nÀ la tombée de la nuit, nous arriverons à notre camp berbère traditionnel où un accueil chaleureux vous attend. Profitez d'une cuisine tunisienne authentique autour du feu de camp, de la musique traditionnelle sous les étoiles et de conversations fascinantes avec les nomades locaux qui appellent ce désert leur maison.",
            ],
            'highlights' => [
                [
                    'en' => 'Sunset camel trek across towering sand dunes',
                    'fr' => 'Trek à dos de chameau au coucher du soleil à travers des dunes de sable imposantes',
                ],
                [
                    'en' => 'Traditional Berber camp experience with authentic local cuisine',
                    'fr' => 'Expérience de camp berbère traditionnel avec une cuisine locale authentique',
                ],
                [
                    'en' => 'Expert Berber guides sharing desert survival techniques',
                    'fr' => 'Guides berbères experts partageant des techniques de survie dans le désert',
                ],
                [
                    'en' => 'Stargazing in one of the world\'s clearest night skies',
                    'fr' => 'Observation des étoiles dans l\'un des ciels nocturnes les plus clairs du monde',
                ],
                [
                    'en' => 'Small group size (max 12) for personalized attention',
                    'fr' => 'Petit groupe (max 12) pour une attention personnalisée',
                ],
            ],
            'included' => [
                [
                    'en' => 'Round-trip transportation from Douz in 4x4 vehicles',
                    'fr' => 'Transport aller-retour depuis Douz en véhicules 4x4',
                ],
                [
                    'en' => 'Professional Berber guide (English & French)',
                    'fr' => 'Guide berbère professionnel (anglais et français)',
                ],
                [
                    'en' => 'Camel ride at sunset (approx. 1.5 hours)',
                    'fr' => 'Balade à dos de chameau au coucher du soleil (env. 1,5 heure)',
                ],
                [
                    'en' => 'Traditional Tunisian dinner at desert camp',
                    'fr' => 'Dîner tunisien traditionnel au camp du désert',
                ],
                [
                    'en' => 'Mint tea and traditional music around the campfire',
                    'fr' => 'Thé à la menthe et musique traditionnelle autour du feu de camp',
                ],
                [
                    'en' => 'All safety equipment and drinking water',
                    'fr' => 'Tout l\'équipement de sécurité et eau potable',
                ],
            ],
            'not_included' => [
                [
                    'en' => 'Personal travel insurance',
                    'fr' => 'Assurance voyage personnelle',
                ],
                [
                    'en' => 'Gratuities for guides (optional)',
                    'fr' => 'Pourboires pour les guides (facultatif)',
                ],
                [
                    'en' => 'Accommodation (day trip only)',
                    'fr' => 'Hébergement (excursion d\'une journée seulement)',
                ],
            ],
            'requirements' => [
                [
                    'en' => 'Moderate fitness level required for camel riding',
                    'fr' => 'Niveau de forme physique modéré requis pour monter à dos de chameau',
                ],
                [
                    'en' => 'Sun protection (hat, sunglasses, sunscreen) essential',
                    'fr' => 'Protection solaire (chapeau, lunettes de soleil, crème solaire) essentielle',
                ],
                [
                    'en' => 'Closed-toe shoes recommended for desert walking',
                    'fr' => 'Chaussures fermées recommandées pour marcher dans le désert',
                ],
            ],
            'meeting_point' => [
                'address' => 'Douz Tourism Office, Avenue Tahar Haddad, Douz, Tunisia',
                'coordinates' => [
                    'latitude' => 33.4667,
                    'longitude' => 9.0167,
                ],
                'instructions' => [
                    'en' => 'Meet at the Douz Tourism Office. Look for our guide with a "Go Adventure" sign. Free parking available nearby.',
                    'fr' => 'Rendez-vous au Bureau du Tourisme de Douz. Cherchez notre guide avec un panneau "Go Adventure". Parking gratuit disponible à proximité.',
                ],
            ],
            'pricing' => [
                'tnd_price' => 180,
                'eur_price' => 55,
                'person_types' => [
                    [
                        'key' => 'adult',
                        'label' => [
                            'en' => 'Adult',
                            'fr' => 'Adulte',
                        ],
                        'tnd_price' => 180,
                        'eur_price' => 55,
                        'min_age' => 12,
                        'max_age' => null,
                        'min_quantity' => 1,
                        'max_quantity' => null,
                    ],
                    [
                        'key' => 'child',
                        'label' => [
                            'en' => 'Child (6-11)',
                            'fr' => 'Enfant (6-11)',
                        ],
                        'tnd_price' => 120,
                        'eur_price' => 37,
                        'min_age' => 6,
                        'max_age' => 11,
                        'min_quantity' => 0,
                        'max_quantity' => null,
                    ],
                ],
                'group_discount' => [
                    'min_size' => 6,
                    'discount_percent' => 10,
                ],
            ],
            'cancellation_policy' => [
                'type' => 'flexible',
                'rules' => [
                    [
                        'hours_before_start' => 24,
                        'refund_percent' => 100,
                    ],
                    [
                        'hours_before_start' => 12,
                        'refund_percent' => 50,
                    ],
                    [
                        'hours_before_start' => 0,
                        'refund_percent' => 0,
                    ],
                ],
                'description' => [
                    'en' => 'Free cancellation up to 24 hours before start time. Cancel between 12-24 hours for 50% refund. No refund for cancellations within 12 hours of start time.',
                    'fr' => 'Annulation gratuite jusqu\'à 24 heures avant l\'heure de départ. Annulez entre 12 et 24 heures pour un remboursement de 50%. Aucun remboursement pour les annulations dans les 12 heures précédant l\'heure de départ.',
                ],
            ],
            'min_group_size' => 2,
            'max_group_size' => 12,
            'duration' => [
                'value' => 8,
                'unit' => 'hours',
            ],
            'difficulty' => DifficultyLevel::MODERATE,
            'distance' => 45,
            'rating' => 4.8,
            'reviews_count' => 127,
            'bookings_count' => 256,
            'published_at' => now(),
            'safety_info' => [
                'required_fitness_level' => [
                    'en' => 'Moderate fitness level - ability to mount and ride a camel',
                    'fr' => 'Niveau de forme physique modéré - capacité à monter et à monter à dos de chameau',
                ],
                'minimum_age' => 6,
                'maximum_age' => null,
                'insurance_required' => false,
                'not_suitable_for' => [
                    [
                        'en' => 'Pregnant women',
                        'fr' => 'Femmes enceintes',
                    ],
                    [
                        'en' => 'People with severe back or knee problems',
                        'fr' => 'Personnes ayant de graves problèmes de dos ou de genoux',
                    ],
                    [
                        'en' => 'Those with extreme heat sensitivity',
                        'fr' => 'Personnes extrêmement sensibles à la chaleur',
                    ],
                ],
                'safety_equipment_provided' => [
                    [
                        'en' => 'First aid kit in all vehicles',
                        'fr' => 'Trousse de premiers secours dans tous les véhicules',
                    ],
                    [
                        'en' => 'Emergency communication equipment',
                        'fr' => 'Équipement de communication d\'urgence',
                    ],
                    [
                        'en' => 'Sun protection (umbrellas at camp)',
                        'fr' => 'Protection solaire (parasols au camp)',
                    ],
                ],
            ],
            'accessibility_info' => [
                'wheelchair_accessible' => false,
                'mobility_aid_accessible' => false,
                'accessible_parking' => true,
                'accessible_restrooms' => true,
                'service_animals_allowed' => false,
                'accessibility_notes' => [
                    'en' => 'This desert tour involves camel riding and walking on sand dunes, which is not suitable for wheelchairs or mobility aids. However, we can arrange a modified 4x4-only tour for guests with limited mobility. Please contact us in advance.',
                    'fr' => 'Cette excursion dans le désert comprend une balade à dos de chameau et une marche sur les dunes de sable, qui ne convient pas aux fauteuils roulants ou aux aides à la mobilité. Cependant, nous pouvons organiser une excursion modifiée en 4x4 uniquement pour les clients à mobilité réduite. Veuillez nous contacter à l\'avance.',
                ],
            ],
            'difficulty_details' => [
                'description' => [
                    'en' => 'Moderate desert adventure with some physical activity',
                    'fr' => 'Aventure dans le désert modérée avec une certaine activité physique',
                ],
                'terrain_type' => [
                    'en' => 'Sand dunes, rocky desert paths, and camp areas',
                    'fr' => 'Dunes de sable, chemins désertiques rocheux et zones de camp',
                ],
                'elevation_gain_meters' => 150,
                'technical_difficulty' => [
                    'en' => 'Beginner-friendly with guide support',
                    'fr' => 'Convivial pour les débutants avec le soutien du guide',
                ],
                'physical_intensity' => [
                    'en' => 'Moderate - includes 1.5 hours of camel riding',
                    'fr' => 'Modérée - comprend 1,5 heure de balade à dos de chameau',
                ],
            ],
        ]);

        // Create comprehensive FAQs
        $faqs = [
            [
                'question' => [
                    'en' => 'What should I wear for the desert tour?',
                    'fr' => 'Que dois-je porter pour l\'excursion dans le désert ?',
                ],
                'answer' => [
                    'en' => 'Wear loose, light-colored clothing that covers your arms and legs to protect from sun and sand. Bring a light scarf or shawl to protect your face from wind. Avoid dark colors as they absorb heat. Closed-toe shoes are essential.',
                    'fr' => 'Portez des vêtements amples et de couleur claire qui couvrent vos bras et vos jambes pour vous protéger du soleil et du sable. Apportez un foulard ou un châle léger pour protéger votre visage du vent. Évitez les couleurs foncées car elles absorbent la chaleur. Les chaussures fermées sont essentielles.',
                ],
                'order' => 0,
            ],
            [
                'question' => [
                    'en' => 'Is this tour suitable for children?',
                    'fr' => 'Cette excursion convient-elle aux enfants ?',
                ],
                'answer' => [
                    'en' => 'Yes! Children aged 6 and above are welcome. Children between 6-11 years receive a discounted rate. Our camels are gentle and well-trained, and our guides are experienced with families. However, children must be supervised by parents at all times.',
                    'fr' => 'Oui! Les enfants âgés de 6 ans et plus sont les bienvenus. Les enfants entre 6 et 11 ans bénéficient d\'un tarif réduit. Nos chameaux sont doux et bien entraînés, et nos guides ont l\'habitude des familles. Cependant, les enfants doivent être surveillés par leurs parents à tout moment.',
                ],
                'order' => 1,
            ],
            [
                'question' => [
                    'en' => 'What happens if the weather is bad?',
                    'fr' => 'Que se passe-t-il si le temps est mauvais ?',
                ],
                'answer' => [
                    'en' => 'The Sahara is generally sunny year-round, but if we experience severe sandstorms or extreme weather, we\'ll contact you 24 hours in advance to reschedule or offer a full refund. Light wind is normal and part of the desert experience.',
                    'fr' => 'Le Sahara est généralement ensoleillé toute l\'année, mais si nous rencontrons de graves tempêtes de sable ou des conditions météorologiques extrêmes, nous vous contacterons 24 heures à l\'avance pour reprogrammer ou offrir un remboursement complet. Le vent léger est normal et fait partie de l\'expérience du désert.',
                ],
                'order' => 2,
            ],
            [
                'question' => [
                    'en' => 'Can I bring my camera and phone?',
                    'fr' => 'Puis-je apporter mon appareil photo et mon téléphone ?',
                ],
                'answer' => [
                    'en' => 'Absolutely! The desert provides incredible photo opportunities. We recommend bringing a protective case or bag to shield your electronics from sand. Our guides know the best spots for sunset photos and will help you capture amazing moments.',
                    'fr' => 'Absolument! Le désert offre des opportunités photographiques incroyables. Nous recommandons d\'apporter une housse de protection ou un sac pour protéger vos appareils électroniques du sable. Nos guides connaissent les meilleurs endroits pour les photos de coucher de soleil et vous aideront à capturer des moments incroyables.',
                ],
                'order' => 3,
            ],
            [
                'question' => [
                    'en' => 'Are meals included and what about dietary restrictions?',
                    'fr' => 'Les repas sont-ils inclus et qu\'en est-il des restrictions alimentaires ?',
                ],
                'answer' => [
                    'en' => 'Yes, a traditional Tunisian dinner is included at the desert camp. We offer vegetarian options and can accommodate most dietary restrictions if you inform us at booking. Bottled water is provided throughout the tour. The meal typically includes couscous, grilled meats (or vegetarian tagine), fresh salads, and traditional sweets.',
                    'fr' => 'Oui, un dîner tunisien traditionnel est inclus au camp du désert. Nous proposons des options végétariennes et pouvons répondre à la plupart des restrictions alimentaires si vous nous en informez lors de la réservation. De l\'eau en bouteille est fournie tout au long de l\'excursion. Le repas comprend généralement du couscous, des viandes grillées (ou un tajine végétarien), des salades fraîches et des douceurs traditionnelles.',
                ],
                'order' => 4,
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

        // Create media with proper categorization
        // Note: Using placeholder images - replace with real URLs
        $mediaItems = [
            [
                'url' => 'https://images.unsplash.com/photo-1509023464722-18d996393ca8',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1509023464722-18d996393ca8?w=400',
                'alt' => 'Sunset over Sahara sand dunes with camels',
                'type' => 'image',
                'category' => MediaCategory::HERO,
                'order' => 0,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1564078516393-cf04bd966897',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1564078516393-cf04bd966897?w=400',
                'alt' => 'Camel caravan in the Sahara Desert',
                'type' => 'image',
                'category' => MediaCategory::FEATURED,
                'order' => 1,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1583009221920-ed936fc3a61d',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1583009221920-ed936fc3a61d?w=400',
                'alt' => 'Traditional Berber desert camp at night',
                'type' => 'image',
                'category' => MediaCategory::FEATURED,
                'order' => 2,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1473186578172-c141e6798cf4',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1473186578172-c141e6798cf4?w=400',
                'alt' => 'Starry night sky over the Sahara',
                'type' => 'image',
                'category' => MediaCategory::FEATURED,
                'order' => 3,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1580060839134-75a5edca2e99?w=400',
                'alt' => 'Desert landscape with palm trees',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 4,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1547036967-23d11aacaee0',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=400',
                'alt' => 'Traditional Tunisian mint tea service',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 5,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1621347505149-3fcbdf6e7480',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1621347505149-3fcbdf6e7480?w=400',
                'alt' => 'Berber guide in traditional dress',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 6,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1609137144813-7d9921338f24',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1609137144813-7d9921338f24?w=400',
                'alt' => 'Golden sand dunes at sunrise',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 7,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1517760444937-f6397edcbbcd',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1517760444937-f6397edcbbcd?w=400',
                'alt' => 'Traditional Tunisian lanterns at desert camp',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 8,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1518391846015-55a9cc003b25',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1518391846015-55a9cc003b25?w=400',
                'alt' => 'Footprints in Sahara sand at sunset',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 9,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1609198092357-efda62bf58de',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1609198092357-efda62bf58de?w=400',
                'alt' => 'Desert wildlife - oryx in natural habitat',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 10,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1613037571582-8d89e0f7a9c3',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1613037571582-8d89e0f7a9c3?w=400',
                'alt' => 'Berber nomad tent interior',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 11,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1558036117-15d82a90b9b1',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1558036117-15d82a90b9b1?w=400',
                'alt' => 'Traditional Tunisian couscous dish',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 12,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1583417319070-4a69db38a482',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1583417319070-4a69db38a482?w=400',
                'alt' => 'Colorful Berber textiles and carpets',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 13,
            ],
            [
                'url' => 'https://images.unsplash.com/photo-1609137164730-d3d46ca36e78',
                'thumbnail_url' => 'https://images.unsplash.com/photo-1609137164730-d3d46ca36e78?w=400',
                'alt' => 'Desert campfire under starlit sky',
                'type' => 'image',
                'category' => MediaCategory::GALLERY,
                'order' => 14,
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

        $this->command->info('✅ Rich demo listing created successfully!');
        $this->command->info('Listing slug: ' . $listing->slug);
        $this->command->info('Media items: ' . count($mediaItems));
        $this->command->info('FAQs: ' . count($faqs));
    }
}
