<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\Location;
use App\Models\Media;
use App\Models\VendorProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoListingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Create high-quality demo listings with Unsplash images
     * Showcasing Tunisia's best destinations
     */
    public function run(): void
    {
        // Get or create locations
        $locations = $this->createLocations();

        // Get first vendor (or create one)
        $vendor = VendorProfile::first() ?? VendorProfile::factory()->create();

        // Create demo listings
        $listings = [
            [
                'title' => [
                    'en' => 'Djerba Island Discovery Tour',
                    'fr' => 'Circuit Découverte de l\'île de Djerba',
                    'ar' => 'جولة اكتشاف جزيرة جربة',
                ],
                'slug' => 'djerba-island-discovery',
                'summary' => [
                    'en' => 'Explore the enchanting island of Djerba with its stunning beaches, ancient synagogue, and traditional markets.',
                    'fr' => 'Explorez l\'île enchanteresse de Djerba avec ses plages magnifiques, sa synagogue ancienne et ses marchés traditionnels.',
                    'ar' => 'استكشف جزيرة جربة الساحرة مع شواطئها الخلابة والكنيس القديم والأسواق التقليدية',
                ],
                'description' => [
                    'en' => 'Join us for an unforgettable journey through Djerba, Tunisia\'s largest island. This full-day tour includes visits to the El Ghriba Synagogue, one of the oldest in Africa, the charming village of Guellala known for its pottery, and pristine beaches. Experience authentic Tunisian culture, enjoy local cuisine, and capture stunning photos of Mediterranean beauty.',
                    'fr' => 'Rejoignez-nous pour un voyage inoubliable à travers Djerba, la plus grande île de Tunisie. Cette excursion d\'une journée comprend des visites à la synagogue El Ghriba, l\'une des plus anciennes d\'Afrique, au charmant village de Guellala connu pour sa poterie, et à des plages immaculées.',
                    'ar' => 'انضم إلينا في رحلة لا تُنسى عبر جربة، أكبر جزيرة في تونس. تشمل هذه الجولة ليوم كامل زيارات إلى كنيس الغريبة، أحد أقدم المعابد في أفريقيا، وقرية قلالة الساحرة المعروفة بالفخار، والشواطئ النقية',
                ],
                'location' => 'djerba',
                'service_type' => 'tour',
                'max_group_size' => 15,
                'min_group_size' => 2,
                'pricing' => [
                    'tnd_price' => 85.00,
                    'eur_price' => 27.00,
                    'display_price' => 85.00,
                    'display_currency' => 'TND',
                    'currency' => 'TND',
                ],
                'duration' => 480, // 8 hours in minutes
                'images' => [
                    'https://images.unsplash.com/photo-1589394043013-78c30032d34c?w=1200', // Djerba beach
                    'https://images.unsplash.com/photo-1583211071853-a04cf0ee85c5?w=1200', // Tunisia architecture
                    'https://images.unsplash.com/photo-1569949380643-6e746ecaa3bd?w=1200', // Mediterranean coast
                ],
            ],
            [
                'title' => [
                    'en' => 'Sahara Desert Camel Trek & Overnight Camp',
                    'fr' => 'Trek à dos de chameau dans le Sahara avec nuit en camp',
                    'ar' => 'رحلة الجمال في الصحراء والتخييم ليلاً',
                ],
                'slug' => 'sahara-desert-camel-trek',
                'summary' => [
                    'en' => 'Experience the magic of the Sahara with a sunset camel trek and overnight camping under the stars.',
                    'fr' => 'Vivez la magie du Sahara avec un trek à dos de chameau au coucher du soleil et un camping sous les étoiles.',
                    'ar' => 'اختبر سحر الصحراء مع رحلة الجمال عند غروب الشمس والتخييم تحت النجوم',
                ],
                'description' => [
                    'en' => 'Embark on an authentic Sahara adventure! Your journey begins with a spectacular camel trek across golden dunes at sunset. As night falls, enjoy a traditional Berber dinner around the campfire, complete with live music. Sleep in comfortable Bedouin tents and wake to a breathtaking desert sunrise. This 2-day experience includes all meals, camping equipment, and an experienced guide.',
                    'fr' => 'Embarquez pour une authentique aventure saharienne ! Votre voyage commence par un spectaculaire trek à dos de chameau à travers les dunes dorées au coucher du soleil. À la tombée de la nuit, savourez un dîner berbère traditionnel autour du feu de camp, avec musique live.',
                    'ar' => 'انطلق في مغامرة صحراوية أصيلة! تبدأ رحلتك برحلة مذهلة على ظهر الجمال عبر الكثبان الذهبية عند غروب الشمس. مع حلول الليل، استمتع بعشاء بربري تقليدي حول النار مع الموسيقى الحية',
                ],
                'location' => 'tozeur',
                'service_type' => 'tour',
                'max_group_size' => 12,
                'min_group_size' => 2,
                'pricing' => [
                    'tnd_price' => 195.00,
                    'eur_price' => 62.00,
                    'display_price' => 195.00,
                    'display_currency' => 'TND',
                    'currency' => 'TND',
                ],
                'duration' => 2880, // 2 days in minutes
                'images' => [
                    'https://images.unsplash.com/photo-1509099836639-18ba1795216d?w=1200', // Sahara desert
                    'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=1200', // Camels in desert
                    'https://images.unsplash.com/photo-1682687220742-aba13b6e50ba?w=1200', // Desert camp
                ],
            ],
            [
                'title' => [
                    'en' => 'Medina of Tunis Walking Tour',
                    'fr' => 'Visite à pied de la Médina de Tunis',
                    'ar' => 'جولة سيراً على الأقدام في مدينة تونس القديمة',
                ],
                'slug' => 'medina-tunis-walking-tour',
                'summary' => [
                    'en' => 'Discover the UNESCO-listed Medina with its labyrinthine streets, historic souks, and architectural wonders.',
                    'fr' => 'Découvrez la Médina classée au patrimoine mondial de l\'UNESCO avec ses rues labyrinthiques, ses souks historiques et ses merveilles architecturales.',
                    'ar' => 'اكتشف المدينة العتيقة المدرجة في قائمة اليونسكو مع شوارعها المتاهة والأسواق التاريخية والعجائب المعمارية',
                ],
                'description' => [
                    'en' => 'Step back in time as we explore the ancient Medina of Tunis, a UNESCO World Heritage site. Navigate narrow, winding streets filled with centuries of history. Visit the magnificent Zitouna Mosque, browse colorful souks selling spices, textiles, and handicrafts, and admire stunning examples of Islamic architecture. Our knowledgeable guide brings the history and culture to life with fascinating stories and insights.',
                    'fr' => 'Remontez le temps en explorant l\'ancienne Médina de Tunis, site du patrimoine mondial de l\'UNESCO. Parcourez les rues étroites et sinueuses imprégnées de siècles d\'histoire. Visitez la magnifique mosquée Zitouna, parcourez les souks colorés vendant épices, textiles et artisanat.',
                    'ar' => 'عُد بالزمن إلى الوراء بينما نستكشف المدينة القديمة في تونس، أحد مواقع التراث العالمي لليونسكو. تجول في الشوارع الضيقة المتعرجة المليئة بقرون من التاريخ. زر جامع الزيتونة الرائع، تصفح الأسواق الملونة',
                ],
                'location' => 'tunis',
                'service_type' => 'tour',
                'max_group_size' => 20,
                'min_group_size' => 4,
                'pricing' => [
                    'tnd_price' => 45.00,
                    'eur_price' => 14.00,
                    'display_price' => 45.00,
                    'display_currency' => 'TND',
                    'currency' => 'TND',
                ],
                'duration' => 240, // 4 hours in minutes
                'images' => [
                    'https://images.unsplash.com/photo-1590492106698-05dc0e19fb26?w=1200', // Tunis medina
                    'https://images.unsplash.com/photo-1563789031959-4c02bcb41319?w=1200', // Tunisian architecture
                    'https://images.unsplash.com/photo-1547036967-23d11aacaee0?w=1200', // Souk
                ],
            ],
            [
                'title' => [
                    'en' => 'Tunisian Cooking Masterclass',
                    'fr' => 'Cours de cuisine tunisienne',
                    'ar' => 'ماستر كلاس الطبخ التونسي',
                ],
                'slug' => 'tunisian-cooking-masterclass',
                'summary' => [
                    'en' => 'Learn to prepare authentic Tunisian dishes with a local chef in a traditional home setting.',
                    'fr' => 'Apprenez à préparer des plats tunisiens authentiques avec un chef local dans un cadre traditionnel.',
                    'ar' => 'تعلّم تحضير الأطباق التونسية الأصيلة مع طاهٍ محلي في منزل تقليدي',
                ],
                'description' => [
                    'en' => 'Immerse yourself in Tunisian culinary traditions! Join a local chef in their home kitchen for a hands-on cooking experience. Learn to make classic dishes like couscous, brik, and tagine from scratch. Discover the secrets of Tunisian spices and cooking techniques passed down through generations. The class concludes with a delicious feast where you\'ll enjoy the fruits of your labor alongside your fellow food enthusiasts.',
                    'fr' => 'Plongez dans les traditions culinaires tunisiennes ! Rejoignez un chef local dans sa cuisine pour une expérience pratique. Apprenez à préparer des plats classiques comme le couscous, le brik et le tajine de zéro.',
                    'ar' => 'انغمس في تقاليد الطهي التونسي! انضم إلى طاهٍ محلي في مطبخه لتجربة طهي عملية. تعلّم تحضير الأطباق الكلاسيكية مثل الكسكسي والبريك والطاجين من الصفر',
                ],
                'location' => 'tunis',
                'service_type' => 'event',
                'max_group_size' => 8,
                'min_group_size' => 2,
                'pricing' => [
                    'tnd_price' => 75.00,
                    'eur_price' => 24.00,
                    'display_price' => 75.00,
                    'display_currency' => 'TND',
                    'currency' => 'TND',
                ],
                'duration' => 240, // 4 hours in minutes
                'images' => [
                    'https://images.unsplash.com/photo-1556909114-f6e7ad7d3136?w=1200', // Cooking
                    'https://images.unsplash.com/photo-1571997478779-2adcbbe9ab2f?w=1200', // Tunisian food
                    'https://images.unsplash.com/photo-1504674900247-0877df9cc836?w=1200', // Traditional cooking
                ],
            ],
            [
                'title' => [
                    'en' => 'Carthage & Sidi Bou Said Heritage Tour',
                    'fr' => 'Circuit du patrimoine de Carthage et Sidi Bou Saïd',
                    'ar' => 'جولة تراث قرطاج وسيدي بوسعيد',
                ],
                'slug' => 'carthage-sidi-bou-said-tour',
                'summary' => [
                    'en' => 'Visit ancient Roman ruins in Carthage and the picturesque blue-and-white village of Sidi Bou Said.',
                    'fr' => 'Visitez les ruines romaines antiques de Carthage et le pittoresque village bleu et blanc de Sidi Bou Saïd.',
                    'ar' => 'قم بزيارة الآثار الرومانية القديمة في قرطاج والقرية الخلابة الزرقاء والبيضاء في سيدي بوسعيد',
                ],
                'description' => [
                    'en' => 'Journey through millennia of history on this captivating day tour. Explore the ruins of ancient Carthage, once a powerful Phoenician city-state and rival to Rome. Walk through the Antonine Baths, Tophet sanctuary, and Roman amphitheater. Then ascend to the stunning clifftop village of Sidi Bou Said, famous for its blue-and-white architecture, art galleries, and panoramic Mediterranean views. Enjoy mint tea at Café des Délices, the same spot where writers and artists have found inspiration for decades.',
                    'fr' => 'Voyagez à travers des millénaires d\'histoire lors de cette journée captivante. Explorez les ruines de l\'antique Carthage, autrefois puissante cité-État phénicienne et rivale de Rome. Parcourez les thermes d\'Antonin, le sanctuaire du Tophet et l\'amphithéâtre romain.',
                    'ar' => 'سافر عبر آلاف السنين من التاريخ في هذه الجولة اليومية الآسرة. استكشف آثار قرطاج القديمة، التي كانت ذات يوم دولة مدينة فينيقية قوية ومنافسة لروما. تجول في حمامات أنطونين ومعبد توفيت والمدرج الروماني',
                ],
                'location' => 'tunis',
                'service_type' => 'tour',
                'max_group_size' => 16,
                'min_group_size' => 2,
                'pricing' => [
                    'tnd_price' => 90.00,
                    'eur_price' => 29.00,
                    'display_price' => 90.00,
                    'display_currency' => 'TND',
                    'currency' => 'TND',
                ],
                'duration' => 360, // 6 hours in minutes
                'images' => [
                    'https://images.unsplash.com/photo-1590041794748-2d89c314883c?w=1200', // Carthage ruins
                    'https://images.unsplash.com/photo-1583211071853-a04cf0ee85c5?w=1200', // Sidi Bou Said
                    'https://images.unsplash.com/photo-1564859228273-274232fdb516?w=1200', // Tunisia view
                ],
            ],
        ];

        // Create listings
        foreach ($listings as $listingData) {
            $location = $locations[$listingData['location']];
            $images = $listingData['images'];
            unset($listingData['location'], $listingData['images']);

            // Calculate coordinates near the location
            $listing = Listing::create([
                ...$listingData,
                'vendor_profile_id' => $vendor->id,
                'location_id' => $location->id,
                'status' => 'published',
                'is_featured' => true,
                'meeting_point' => [
                    'address' => $location->name . ', Tunisia',
                    'lat' => $location->latitude + (rand(-1000, 1000) / 10000),
                    'lng' => $location->longitude + (rand(-1000, 1000) / 10000),
                    'instructions' => [
                        'en' => 'Meeting point details will be provided after booking confirmation.',
                        'fr' => 'Les détails du point de rendez-vous seront fournis après la confirmation de la réservation.',
                        'ar' => 'سيتم توفير تفاصيل نقطة الالتقاء بعد تأكيد الحجز',
                    ],
                ],
                'included' => [
                    'en' => ['Professional guide', 'Transportation', 'Entrance fees', 'Refreshments'],
                    'fr' => ['Guide professionnel', 'Transport', 'Frais d\'entrée', 'Rafraîchissements'],
                    'ar' => ['مرشد محترف', 'المواصلات', 'رسوم الدخول', 'المرطبات'],
                ],
                'excluded' => [
                    'en' => ['Personal expenses', 'Meals not mentioned', 'Travel insurance', 'Tips'],
                    'fr' => ['Dépenses personnelles', 'Repas non mentionnés', 'Assurance voyage', 'Pourboires'],
                    'ar' => ['النفقات الشخصية', 'الوجبات غير المذكورة', 'التأمين على السفر', 'البقشيش'],
                ],
                'cancellation_policy' => [
                    'en' => 'Free cancellation up to 24 hours before the activity starts. No refund for cancellations within 24 hours.',
                    'fr' => 'Annulation gratuite jusqu\'à 24 heures avant le début de l\'activité. Aucun remboursement pour les annulations dans les 24 heures.',
                    'ar' => 'إلغاء مجاني حتى 24 ساعة قبل بدء النشاط. لا يوجد استرداد للإلغاءات في غضون 24 ساعة',
                ],
            ]);

            // Create media records
            foreach ($images as $index => $url) {
                Media::create([
                    'mediable_type' => Listing::class,
                    'mediable_id' => $listing->id,
                    'type' => 'image',
                    'url' => $url,
                    'alt' => $listingData['title'],
                    'order' => $index + 1,
                ]);
            }

            echo "✅ Created listing: {$listingData['slug']}\n";
        }

        echo "\n🎉 Successfully created " . count($listings) . " demo listings with images!\n";
    }

    private function createLocations(): array
    {
        // Use existing locations by slug
        $slugs = ['djerba', 'tozeur', 'tunis'];
        $locations = [];

        foreach ($slugs as $slug) {
            $location = Location::where('slug', $slug)->first();

            if ($location) {
                $locations[$slug] = $location;
                echo "📍 Using existing location: {$slug}\n";
            } else {
                echo "⚠️ Warning: Location '{$slug}' not found in database\n";
            }
        }

        return $locations;
    }
}
