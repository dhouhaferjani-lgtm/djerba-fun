<?php

namespace Database\Seeders;

use App\Enums\ListingStatus;
use App\Enums\ServiceType;
use App\Models\Listing;
use App\Models\Location;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $vendor = User::where('role', 'vendor')->first();

        if (! $vendor) {
            $vendor = User::first();
        }

        $locations = Location::all()->keyBy('slug');

        // Tours
        $tours = [
            [
                'location' => 'djerba',
                'title' => ['en' => 'Djerba Island Discovery Tour', 'fr' => 'Tour Découverte de l\'île de Djerba'],
                'slug' => 'djerba-island-discovery-tour',
                'summary' => [
                    'en' => 'Explore the enchanting island of Djerba with its stunning beaches, ancient synagogue, and vibrant souks.',
                    'fr' => 'Explorez l\'île enchanteresse de Djerba avec ses plages magnifiques, sa synagogue ancienne et ses souks animés.',
                ],
                'description' => [
                    'en' => 'Embark on a full-day adventure exploring Djerba, the Island of Dreams. Visit the ancient El Ghriba Synagogue, wander through the colorful streets of Houmt Souk, and relax on pristine beaches. This eco-friendly tour includes traditional lunch and authentic cultural experiences.',
                    'fr' => 'Embarquez pour une aventure d\'une journée complète à la découverte de Djerba, l\'île des Rêves. Visitez l\'ancienne synagogue El Ghriba, promenez-vous dans les rues colorées de Houmt Souk et détendez-vous sur des plages immaculées. Ce tour éco-responsable comprend un déjeuner traditionnel et des expériences culturelles authentiques.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 8, 'unit' => 'hours'],
                'price' => 85,
                'image' => 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=800',
            ],
            [
                'location' => 'sahara-desert',
                'title' => ['en' => 'Sahara Desert Camel Trek', 'fr' => 'Randonnée à Dos de Chameau dans le Sahara'],
                'slug' => 'sahara-desert-camel-trek',
                'summary' => [
                    'en' => 'Experience the magic of the Sahara with a sunset camel trek and overnight stay in a Berber camp.',
                    'fr' => 'Vivez la magie du Sahara avec une randonnée à dos de chameau au coucher du soleil et une nuit dans un camp berbère.',
                ],
                'description' => [
                    'en' => 'Journey into the heart of the Tunisian Sahara on this unforgettable 2-day adventure. Ride camels through golden dunes at sunset, sleep under the stars in an authentic Berber camp, and wake to a spectacular desert sunrise. This sustainable tour supports local Berber communities.',
                    'fr' => 'Partez au cœur du Sahara tunisien pour cette aventure inoubliable de 2 jours. Montez à dos de chameau à travers les dunes dorées au coucher du soleil, dormez sous les étoiles dans un camp berbère authentique et réveillez-vous devant un lever de soleil spectaculaire dans le désert. Ce tour durable soutient les communautés berbères locales.',
                ],
                'difficulty' => 'moderate',
                'duration' => ['value' => 2, 'unit' => 'days'],
                'price' => 195,
                'image' => 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=800',
            ],
            [
                'location' => 'tunis',
                'title' => ['en' => 'Medina of Tunis Walking Tour', 'fr' => 'Visite à Pied de la Médina de Tunis'],
                'slug' => 'medina-tunis-walking-tour',
                'summary' => [
                    'en' => 'Discover the UNESCO-listed Medina of Tunis with its labyrinthine streets, historic souks, and hidden gems.',
                    'fr' => 'Découvrez la Médina de Tunis classée au patrimoine mondial de l\'UNESCO avec ses rues labyrinthiques, ses souks historiques et ses trésors cachés.',
                ],
                'description' => [
                    'en' => 'Step back in time as you explore one of the best-preserved Arab-Muslim cities in the world. Our expert local guide will lead you through centuries-old souks, magnificent mosques, and traditional craftsmen workshops. Includes traditional mint tea and local snacks.',
                    'fr' => 'Remontez le temps en explorant l\'une des villes arabo-musulmanes les mieux préservées au monde. Notre guide local expert vous conduira à travers des souks centenaires, des mosquées magnifiques et des ateliers d\'artisans traditionnels. Comprend un thé à la menthe traditionnel et des collations locales.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 4, 'unit' => 'hours'],
                'price' => 45,
                'image' => 'https://images.unsplash.com/photo-1590492106698-05dc0e19fb26?w=800',
            ],
            [
                'location' => 'sidi-bou-said',
                'title' => ['en' => 'Sidi Bou Said Art & Culture Tour', 'fr' => 'Tour Art et Culture de Sidi Bou Saïd'],
                'slug' => 'sidi-bou-said-art-culture-tour',
                'summary' => [
                    'en' => 'Immerse yourself in the artistic heritage of the iconic blue and white village overlooking the Mediterranean.',
                    'fr' => 'Plongez dans le patrimoine artistique de l\'emblématique village bleu et blanc surplombant la Méditerranée.',
                ],
                'description' => [
                    'en' => 'Wander through the picturesque streets of Sidi Bou Said, a village that has inspired artists for centuries. Visit art galleries, enjoy panoramic Mediterranean views, and experience traditional Tunisian coffee at the famous Café des Nattes. Perfect for photography enthusiasts.',
                    'fr' => 'Promenez-vous dans les rues pittoresques de Sidi Bou Saïd, un village qui inspire les artistes depuis des siècles. Visitez des galeries d\'art, profitez de vues panoramiques sur la Méditerranée et dégustez un café tunisien traditionnel au célèbre Café des Nattes. Parfait pour les passionnés de photographie.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 3, 'unit' => 'hours'],
                'price' => 35,
                'image' => 'https://images.unsplash.com/photo-1568797629192-789acf8e4df3?w=800',
            ],
            [
                'location' => 'tozeur',
                'title' => ['en' => 'Star Wars Filming Locations Tour', 'fr' => 'Tour des Lieux de Tournage de Star Wars'],
                'slug' => 'star-wars-filming-locations-tour',
                'summary' => [
                    'en' => 'Visit the iconic Star Wars filming locations including Mos Espa and the Lars Homestead in the Tunisian desert.',
                    'fr' => 'Visitez les lieux de tournage emblématiques de Star Wars, notamment Mos Espa et la ferme Lars dans le désert tunisien.',
                ],
                'description' => [
                    'en' => 'For Star Wars fans, this is a dream come true! Explore the original filming locations from the iconic saga, including the Mos Espa set, the Lars Homestead, and the stunning canyon landscapes of Tatooine. Our passionate guide brings the movies to life with behind-the-scenes stories.',
                    'fr' => 'Pour les fans de Star Wars, c\'est un rêve devenu réalité ! Explorez les lieux de tournage originaux de la saga emblématique, notamment le décor de Mos Espa, la ferme Lars et les paysages de canyon époustouflants de Tatooine. Notre guide passionné donne vie aux films avec des histoires de coulisses.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 6, 'unit' => 'hours'],
                'price' => 75,
                'image' => 'https://images.unsplash.com/photo-1548020920-3e8e6d2b7d0e?w=800',
            ],
            [
                'location' => 'carthage',
                'title' => ['en' => 'Carthage Archaeological Discovery', 'fr' => 'Découverte Archéologique de Carthage'],
                'slug' => 'carthage-archaeological-discovery',
                'summary' => [
                    'en' => 'Explore the ancient ruins of Carthage, once the most powerful city in the Mediterranean world.',
                    'fr' => 'Explorez les ruines antiques de Carthage, autrefois la ville la plus puissante du monde méditerranéen.',
                ],
                'description' => [
                    'en' => 'Walk in the footsteps of ancient civilizations at the UNESCO World Heritage site of Carthage. Discover Roman villas, Punic ports, and the magnificent Antonine Baths. Our expert archaeologist guide brings 3,000 years of history to life.',
                    'fr' => 'Marchez sur les traces des civilisations anciennes sur le site du patrimoine mondial de l\'UNESCO de Carthage. Découvrez les villas romaines, les ports puniques et les magnifiques thermes d\'Antonin. Notre guide archéologue expert donne vie à 3 000 ans d\'histoire.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 4, 'unit' => 'hours'],
                'price' => 55,
                'image' => 'https://images.unsplash.com/photo-1564507592333-c60657eea523?w=800',
            ],
            [
                'location' => 'matmata',
                'title' => ['en' => 'Troglodyte Village Experience', 'fr' => 'Expérience du Village Troglodyte'],
                'slug' => 'troglodyte-village-experience',
                'summary' => [
                    'en' => 'Discover the unique underground homes of Matmata, featured as Luke Skywalker\'s home in Star Wars.',
                    'fr' => 'Découvrez les maisons souterraines uniques de Matmata, présentées comme la maison de Luke Skywalker dans Star Wars.',
                ],
                'description' => [
                    'en' => 'Experience the fascinating troglodyte architecture of Matmata, where Berber families have lived underground for centuries. Visit the famous Hotel Sidi Driss (Luke\'s childhood home in Star Wars) and enjoy traditional Berber hospitality with tea and local cuisine.',
                    'fr' => 'Découvrez l\'architecture troglodyte fascinante de Matmata, où les familles berbères vivent sous terre depuis des siècles. Visitez le célèbre Hôtel Sidi Driss (la maison d\'enfance de Luke dans Star Wars) et profitez de l\'hospitalité berbère traditionnelle avec du thé et de la cuisine locale.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 5, 'unit' => 'hours'],
                'price' => 65,
                'image' => 'https://images.unsplash.com/photo-1548020920-3e8e6d2b7d0e?w=800',
            ],
            [
                'location' => 'kairouan',
                'title' => ['en' => 'Kairouan Spiritual Heritage Tour', 'fr' => 'Tour du Patrimoine Spirituel de Kairouan'],
                'slug' => 'kairouan-spiritual-heritage-tour',
                'summary' => [
                    'en' => 'Visit the fourth holiest city in Islam and discover its magnificent mosques and rich spiritual heritage.',
                    'fr' => 'Visitez la quatrième ville sainte de l\'Islam et découvrez ses magnifiques mosquées et son riche patrimoine spirituel.',
                ],
                'description' => [
                    'en' => 'Explore Kairouan, a UNESCO World Heritage city and one of Islam\'s most sacred places. Visit the Great Mosque, admire traditional carpet weaving, and taste the famous makroudh pastries. A deeply enriching cultural and spiritual experience.',
                    'fr' => 'Explorez Kairouan, ville classée au patrimoine mondial de l\'UNESCO et l\'un des lieux les plus sacrés de l\'Islam. Visitez la Grande Mosquée, admirez le tissage traditionnel de tapis et goûtez aux célèbres pâtisseries makroudh. Une expérience culturelle et spirituelle profondément enrichissante.',
                ],
                'difficulty' => 'easy',
                'duration' => ['value' => 6, 'unit' => 'hours'],
                'price' => 70,
                'image' => 'https://images.unsplash.com/photo-1590059390047-f5e617690a0b?w=800',
            ],
        ];

        foreach ($tours as $tourData) {
            $location = $locations->get($tourData['location']);

            if (! $location) {
                continue;
            }

            $listing = Listing::create([
                'vendor_id' => $vendor->id,
                'location_id' => $location->id,
                'service_type' => ServiceType::TOUR,
                'status' => ListingStatus::PUBLISHED,
                'title' => $tourData['title'],
                'slug' => $tourData['slug'],
                'summary' => $tourData['summary'],
                'description' => $tourData['description'],
                'highlights' => [
                    ['en' => 'Expert local guide', 'fr' => 'Guide local expert'],
                    ['en' => 'Small group experience', 'fr' => 'Expérience en petit groupe'],
                    ['en' => 'Eco-friendly practices', 'fr' => 'Pratiques éco-responsables'],
                ],
                'included' => [
                    ['en' => 'Professional guide', 'fr' => 'Guide professionnel'],
                    ['en' => 'Transportation', 'fr' => 'Transport'],
                    ['en' => 'Refreshments', 'fr' => 'Rafraîchissements'],
                ],
                'not_included' => [
                    ['en' => 'Personal expenses', 'fr' => 'Dépenses personnelles'],
                    ['en' => 'Tips', 'fr' => 'Pourboires'],
                ],
                'requirements' => [
                    ['en' => 'Comfortable walking shoes', 'fr' => 'Chaussures de marche confortables'],
                    ['en' => 'Sun protection', 'fr' => 'Protection solaire'],
                ],
                'meeting_point' => [
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'instructions' => ['en' => 'Meet at the main entrance', 'fr' => 'Rendez-vous à l\'entrée principale'],
                ],
                'pricing' => [
                    'base_price' => $tourData['price'],
                    'currency' => 'TND',
                    'per_person' => true,
                ],
                'cancellation_policy' => [
                    'type' => 'flexible',
                    'description' => ['en' => 'Free cancellation up to 24 hours before', 'fr' => 'Annulation gratuite jusqu\'à 24 heures avant'],
                ],
                'min_group_size' => 1,
                'max_group_size' => 12,
                'duration' => $tourData['duration'],
                'difficulty' => $tourData['difficulty'],
                'rating' => rand(40, 50) / 10,
                'reviews_count' => rand(10, 50),
                'bookings_count' => rand(20, 100),
                'published_at' => now()->subDays(rand(1, 30)),
            ]);

            // Add media
            Media::create([
                'mediable_type' => Listing::class,
                'mediable_id' => $listing->id,
                'url' => $tourData['image'],
                'alt' => $tourData['title']['en'],
                'type' => 'image',
                'order' => 0,
            ]);
        }

        // Events
        $events = [
            [
                'location' => 'djerba',
                'title' => ['en' => 'Djerba Music Festival 2025', 'fr' => 'Festival de Musique de Djerba 2025'],
                'slug' => 'djerba-music-festival-2025',
                'summary' => [
                    'en' => 'Three days of world music, traditional Tunisian performances, and international artists on the beach.',
                    'fr' => 'Trois jours de musique du monde, de spectacles tunisiens traditionnels et d\'artistes internationaux sur la plage.',
                ],
                'description' => [
                    'en' => 'Join us for the biggest music festival in Tunisia! Experience a unique blend of traditional Tunisian music, world beats, and contemporary sounds in the stunning setting of Djerba\'s beaches. Features local and international artists, food stalls, and cultural workshops.',
                    'fr' => 'Rejoignez-nous pour le plus grand festival de musique en Tunisie ! Vivez un mélange unique de musique tunisienne traditionnelle, de rythmes du monde et de sons contemporains dans le cadre magnifique des plages de Djerba. Artistes locaux et internationaux, stands de nourriture et ateliers culturels.',
                ],
                'event_type' => 'festival',
                'start_date' => now()->addMonths(2),
                'end_date' => now()->addMonths(2)->addDays(3),
                'price' => 120,
                'image' => 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800',
            ],
            [
                'location' => 'tunis',
                'title' => ['en' => 'Traditional Pottery Workshop', 'fr' => 'Atelier de Poterie Traditionnelle'],
                'slug' => 'traditional-pottery-workshop',
                'summary' => [
                    'en' => 'Learn the ancient art of Tunisian pottery from master craftsmen in a historic medina workshop.',
                    'fr' => 'Apprenez l\'art ancien de la poterie tunisienne auprès de maîtres artisans dans un atelier historique de la médina.',
                ],
                'description' => [
                    'en' => 'Immerse yourself in centuries-old traditions as you learn pottery techniques passed down through generations. Work with local clay, learn traditional patterns, and create your own piece to take home. All materials and refreshments included.',
                    'fr' => 'Plongez dans des traditions séculaires en apprenant des techniques de poterie transmises de génération en génération. Travaillez avec de l\'argile locale, apprenez les motifs traditionnels et créez votre propre pièce à emporter. Tous les matériaux et rafraîchissements inclus.',
                ],
                'event_type' => 'workshop',
                'start_date' => now()->addWeeks(1),
                'end_date' => now()->addWeeks(1)->addHours(4),
                'price' => 55,
                'image' => 'https://images.unsplash.com/photo-1565193566173-7a0ee3dbe261?w=800',
            ],
            [
                'location' => 'sahara-desert',
                'title' => ['en' => 'Sahara Stargazing Night', 'fr' => 'Nuit d\'Observation des Étoiles au Sahara'],
                'slug' => 'sahara-stargazing-night',
                'summary' => [
                    'en' => 'Experience the clearest night skies on Earth with expert astronomers in the heart of the Sahara.',
                    'fr' => 'Vivez les ciels nocturnes les plus clairs de la Terre avec des astronomes experts au cœur du Sahara.',
                ],
                'description' => [
                    'en' => 'Escape light pollution and witness the Milky Way like never before. Professional astronomers guide you through constellations, planets, and galaxies using high-powered telescopes. Includes traditional dinner, Berber music, and overnight camping option.',
                    'fr' => 'Échappez à la pollution lumineuse et contemplez la Voie Lactée comme jamais auparavant. Des astronomes professionnels vous guident à travers les constellations, les planètes et les galaxies à l\'aide de télescopes puissants. Comprend un dîner traditionnel, de la musique berbère et une option de camping.',
                ],
                'event_type' => 'experience',
                'start_date' => now()->addWeeks(2),
                'end_date' => now()->addWeeks(2)->addHours(8),
                'price' => 89,
                'image' => 'https://images.unsplash.com/photo-1507400492013-162706c8c05e?w=800',
            ],
            [
                'location' => 'sidi-bou-said',
                'title' => ['en' => 'Tunisian Cooking Masterclass', 'fr' => 'Masterclass de Cuisine Tunisienne'],
                'slug' => 'tunisian-cooking-masterclass',
                'summary' => [
                    'en' => 'Learn to prepare authentic Tunisian dishes with a local chef in a beautiful traditional home.',
                    'fr' => 'Apprenez à préparer des plats tunisiens authentiques avec un chef local dans une belle maison traditionnelle.',
                ],
                'description' => [
                    'en' => 'Master the secrets of Tunisian cuisine in this hands-on cooking class. Learn to make couscous, brik, and traditional salads using fresh local ingredients. Enjoy your creations with panoramic Mediterranean views. Recipes and certificate included.',
                    'fr' => 'Maîtrisez les secrets de la cuisine tunisienne dans ce cours de cuisine pratique. Apprenez à préparer le couscous, le brik et les salades traditionnelles avec des ingrédients locaux frais. Dégustez vos créations avec une vue panoramique sur la Méditerranée. Recettes et certificat inclus.',
                ],
                'event_type' => 'workshop',
                'start_date' => now()->addDays(5),
                'end_date' => now()->addDays(5)->addHours(4),
                'price' => 75,
                'image' => 'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=800',
            ],
            [
                'location' => 'kairouan',
                'title' => ['en' => 'Carpet Weaving Workshop', 'fr' => 'Atelier de Tissage de Tapis'],
                'slug' => 'carpet-weaving-workshop',
                'summary' => [
                    'en' => 'Discover the ancient art of Kairouan carpet weaving with master artisans.',
                    'fr' => 'Découvrez l\'art ancien du tissage de tapis de Kairouan avec des maîtres artisans.',
                ],
                'description' => [
                    'en' => 'Learn traditional carpet weaving techniques from the artisans of Kairouan, famous worldwide for their intricate designs. Understand the symbolism behind patterns and try your hand at this centuries-old craft. Materials and traditional lunch included.',
                    'fr' => 'Apprenez les techniques traditionnelles de tissage de tapis auprès des artisans de Kairouan, mondialement célèbres pour leurs motifs complexes. Comprenez le symbolisme derrière les motifs et essayez-vous à cet artisanat séculaire. Matériaux et déjeuner traditionnel inclus.',
                ],
                'event_type' => 'workshop',
                'start_date' => now()->addWeeks(3),
                'end_date' => now()->addWeeks(3)->addHours(6),
                'price' => 95,
                'image' => 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800',
            ],
            [
                'location' => 'tozeur',
                'title' => ['en' => 'Desert Photography Expedition', 'fr' => 'Expédition Photo dans le Désert'],
                'slug' => 'desert-photography-expedition',
                'summary' => [
                    'en' => 'Capture stunning desert landscapes with professional photography guidance at golden hour.',
                    'fr' => 'Capturez de superbes paysages désertiques avec des conseils de photographie professionnelle à l\'heure dorée.',
                ],
                'description' => [
                    'en' => 'Join professional photographers for an exclusive expedition capturing the magical light of the Tunisian desert. Learn advanced techniques for landscape, portrait, and night photography. Includes 4x4 transport, dinner, and post-processing workshop.',
                    'fr' => 'Rejoignez des photographes professionnels pour une expédition exclusive capturant la lumière magique du désert tunisien. Apprenez des techniques avancées de photographie de paysage, de portrait et de nuit. Comprend le transport en 4x4, le dîner et un atelier de post-traitement.',
                ],
                'event_type' => 'experience',
                'start_date' => now()->addMonths(1),
                'end_date' => now()->addMonths(1)->addDays(1),
                'price' => 150,
                'image' => 'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=800',
            ],
        ];

        foreach ($events as $eventData) {
            $location = $locations->get($eventData['location']);

            if (! $location) {
                continue;
            }

            $listing = Listing::create([
                'vendor_id' => $vendor->id,
                'location_id' => $location->id,
                'service_type' => ServiceType::EVENT,
                'status' => ListingStatus::PUBLISHED,
                'title' => $eventData['title'],
                'slug' => $eventData['slug'],
                'summary' => $eventData['summary'],
                'description' => $eventData['description'],
                'highlights' => [
                    ['en' => 'Unique experience', 'fr' => 'Expérience unique'],
                    ['en' => 'Expert instructors', 'fr' => 'Instructeurs experts'],
                    ['en' => 'All materials included', 'fr' => 'Tout le matériel inclus'],
                ],
                'included' => [
                    ['en' => 'All equipment', 'fr' => 'Tout l\'équipement'],
                    ['en' => 'Refreshments', 'fr' => 'Rafraîchissements'],
                    ['en' => 'Certificate', 'fr' => 'Certificat'],
                ],
                'not_included' => [
                    ['en' => 'Transportation to venue', 'fr' => 'Transport jusqu\'au lieu'],
                    ['en' => 'Personal items', 'fr' => 'Objets personnels'],
                ],
                'requirements' => [
                    ['en' => 'No prior experience needed', 'fr' => 'Aucune expérience préalable requise'],
                ],
                'meeting_point' => [
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                    'instructions' => ['en' => 'Arrive 15 minutes early', 'fr' => 'Arrivez 15 minutes en avance'],
                ],
                'pricing' => [
                    'base_price' => $eventData['price'],
                    'currency' => 'TND',
                    'per_person' => true,
                ],
                'cancellation_policy' => [
                    'type' => 'moderate',
                    'description' => ['en' => 'Free cancellation up to 48 hours before', 'fr' => 'Annulation gratuite jusqu\'à 48 heures avant'],
                ],
                'min_group_size' => 1,
                'max_group_size' => 20,
                'event_type' => $eventData['event_type'],
                'start_date' => $eventData['start_date'],
                'end_date' => $eventData['end_date'],
                'venue' => [
                    'name' => $location->name,
                    'address' => $location->address,
                    'latitude' => $location->latitude,
                    'longitude' => $location->longitude,
                ],
                'rating' => rand(40, 50) / 10,
                'reviews_count' => rand(5, 30),
                'bookings_count' => rand(10, 60),
                'published_at' => now()->subDays(rand(1, 20)),
            ]);

            // Add media
            Media::create([
                'mediable_type' => Listing::class,
                'mediable_id' => $listing->id,
                'url' => $eventData['image'],
                'alt' => $eventData['title']['en'],
                'type' => 'image',
                'order' => 0,
            ]);
        }
    }
}
