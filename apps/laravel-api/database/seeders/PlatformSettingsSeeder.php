<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlatformSettings;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    /**
     * Seed the platform settings with default values.
     */
    public function run(): void
    {
        // Only create if none exists (singleton pattern)
        if (PlatformSettings::exists()) {
            $this->command->info('Platform settings already exist, skipping...');

            return;
        }

        PlatformSettings::create([
            // Platform Identity - Djerba Fun
            'platform_name' => [
                'en' => 'Djerba Fun',
                'fr' => 'Djerba Fun',
            ],
            'tagline' => [
                'en' => 'Experience the island differently',
                'fr' => 'Vivez l\'île autrement',
            ],
            'description' => [
                'en' => 'Discover Djerba island with unique tours, nautical activities, and authentic accommodations. Your Mediterranean adventure starts here!',
                'fr' => 'Découvrez l\'île de Djerba avec des excursions uniques, des activités nautiques et des hébergements authentiques. Votre aventure méditerranéenne commence ici!',
            ],
            'primary_domain' => 'https://djerbafun.com',
            'api_url' => env('APP_URL', 'http://localhost:8000'),
            'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

            // Hero Section
            'hero_title' => [
                'en' => 'Discover Djerba',
                'fr' => 'Découvrez Djerba',
            ],
            'hero_subtitle' => [
                'en' => 'Your Mediterranean adventure awaits',
                'fr' => 'Votre aventure méditerranéenne vous attend',
            ],

            // Brand Pillars
            'pillar_1_title' => [
                'en' => 'Adventure',
                'fr' => 'Aventure',
            ],
            'pillar_1_description' => [
                'en' => 'Thrilling experiences await on land and sea',
                'fr' => 'Des expériences palpitantes vous attendent sur terre et en mer',
            ],
            'pillar_2_title' => [
                'en' => 'Culture',
                'fr' => 'Culture',
            ],
            'pillar_2_description' => [
                'en' => 'Immerse yourself in local traditions',
                'fr' => 'Plongez dans les traditions locales',
            ],
            'pillar_3_title' => [
                'en' => 'Relaxation',
                'fr' => 'Détente',
            ],
            'pillar_3_description' => [
                'en' => 'Unwind on pristine beaches',
                'fr' => 'Détendez-vous sur des plages immaculées',
            ],

            // Event of the Year
            'event_of_year_title' => [
                'en' => 'Featured Event',
                'fr' => 'Événement à la Une',
            ],

            // SEO & Metadata
            'meta_title' => [
                'en' => 'Djerba Fun - Tours, Nautical Activities & Accommodations',
                'fr' => 'Djerba Fun - Excursions, Activités Nautiques & Hébergements',
            ],
            'meta_description' => [
                'en' => 'Discover Djerba island: tours, jet ski, parasailing, diving, and authentic accommodations. Book your Mediterranean escape today!',
                'fr' => 'Découvrez l\'île de Djerba : excursions, jet ski, parachute ascensionnel, plongée et hébergements authentiques. Réservez votre évasion méditerranéenne!',
            ],
            'keywords' => ['djerba', 'tunisie', 'tunisia', 'tours', 'nautical', 'jet ski', 'parasailing', 'accommodation', 'mediterranean'],
            'author' => 'Djerba Fun',
            'organization_type' => 'TravelAgency',
            'founded_year' => 2024,

            // Contact Information
            'support_email' => 'support@djerbafun.com',
            'general_email' => 'contact@djerbafun.com',
            'phone_number' => '+216 75 123 456',
            'whatsapp_number' => '+21675123456',
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '09:00', 'close' => '18:00'],
                'saturday' => ['open' => '10:00', 'close' => '16:00'],
                'sunday' => null,
            ],

            // Physical Address - Djerba, Tunisia
            'address_street' => 'Zone Touristique Midoun',
            'address_city' => 'Djerba',
            'address_region' => 'Medenine',
            'address_postal_code' => '4116',
            'address_country' => 'TN',
            'google_maps_url' => 'https://maps.google.com/?q=Djerba,+Tunisia',

            // Social Media
            'social_facebook' => 'https://facebook.com/djerbafun',
            'social_instagram' => 'https://instagram.com/djerbafun',
            'social_twitter' => null,
            'social_linkedin' => null,
            'social_youtube' => null,
            'social_tiktok' => 'https://tiktok.com/@djerbafun',

            // Email Settings
            'email_from_name' => 'Djerba Fun',
            'email_from_address' => 'noreply@djerbafun.com',
            'email_reply_to' => 'support@djerbafun.com',
            'email_terms_url' => 'https://djerbafun.com/terms',
            'email_privacy_url' => 'https://djerbafun.com/privacy',

            // Payment & Commerce
            'default_currency' => 'TND',
            'enabled_currencies' => ['TND', 'EUR', 'USD'],
            'platform_commission_percent' => 15.00,
            'payment_processing_fee_percent' => 2.90,
            'min_booking_amount' => 10,
            'max_booking_amount' => 10000,
            'default_payment_gateway' => 'mock',
            'enabled_payment_methods' => ['mock', 'offline', 'click_to_pay'],

            // Booking Settings
            'hold_duration_minutes' => 15,
            'hold_warning_minutes' => 3,
            'auto_cancel_hours' => 24,
            'default_cancellation_policy' => [
                'type' => 'moderate',
                'rules' => [
                    ['hoursBeforeStart' => 48, 'refundPercent' => 100],
                    ['hoursBeforeStart' => 24, 'refundPercent' => 50],
                    ['hoursBeforeStart' => 0, 'refundPercent' => 0],
                ],
            ],

            // Localization - French default for Tunisia
            'default_locale' => 'fr',
            'available_locales' => ['fr', 'en'],
            'fallback_locale' => 'fr',
            'rtl_locales' => [],
            'date_format' => 'd MMMM yyyy',
            'time_format' => 'HH:mm',
            'timezone' => 'Africa/Tunis',
            'week_starts_on' => 1, // Monday

            // Feature Flags
            'enable_reviews' => true,
            'enable_wishlists' => true,
            'enable_gift_cards' => false,
            'enable_loyalty_program' => false,
            'enable_partner_api' => true,
            'enable_blog' => true,
            'enable_instant_booking' => true,
            'enable_request_to_book' => false,
            'enable_group_bookings' => true,
            'enable_custom_packages' => false,

            // Analytics & Tracking (empty for dev)
            'ga4_measurement_id' => null,
            'gtm_container_id' => null,
            'google_search_console_verification' => null,
            'google_maps_api_key' => null,
            'facebook_pixel_id' => null,
            'hotjar_site_id' => null,
            'plausible_domain' => null,
            'sentry_dsn' => null,

            // Legal & Compliance
            'terms_url' => 'https://djerbafun.com/terms',
            'privacy_url' => 'https://djerbafun.com/privacy',
            'cookie_policy_url' => 'https://djerbafun.com/cookies',
            'refund_policy_url' => 'https://djerbafun.com/refund-policy',
            'data_deletion_policy_url' => 'https://djerbafun.com/data-deletion',
            'cookie_consent_enabled' => true,
            'gdpr_mode_enabled' => true,
            'data_retention_days' => 365,
            'minimum_age_requirement' => 18,

            // Vendor Settings
            'vendor_auto_approve' => false,
            'vendor_require_kyc' => true,
            'vendor_kyc_document_types' => ['passport', 'id_card', 'business_license'],
            'vendor_commission_rate' => 15.00,
            'vendor_payout_frequency' => 'weekly',
            'vendor_payout_minimum' => 50,
            'vendor_payout_currency' => 'TND',
            'vendor_payout_delay_days' => 7,

            // =========================================================================
            // CMS Section Settings
            // =========================================================================

            // Experience Categories Section
            'experience_categories_enabled' => true,
            'experience_categories_title' => [
                'en' => 'Explore Our Experiences',
                'fr' => 'Explorez Nos Expériences',
            ],
            'experience_categories_subtitle' => [
                'en' => 'Find your perfect adventure in Djerba',
                'fr' => 'Trouvez votre aventure parfaite à Djerba',
            ],
            'experience_categories' => [
                [
                    'id' => 'tour',
                    'display_order' => 0,
                    'name_en' => 'Tours',
                    'name_fr' => 'Excursions',
                    'description_en' => 'Discover guided tours and adventures',
                    'description_fr' => 'Découvrez des excursions et aventures guidées',
                    'image' => null,
                    'link' => '/listings?type=tour',
                ],
                [
                    'id' => 'nautical',
                    'display_order' => 1,
                    'name_en' => 'Nautical Activities',
                    'name_fr' => 'Activités Nautiques',
                    'description_en' => 'Water sports and boat tours',
                    'description_fr' => 'Sports nautiques et tours en bateau',
                    'image' => null,
                    'link' => '/listings?type=nautical',
                ],
                [
                    'id' => 'accommodation',
                    'display_order' => 2,
                    'name_en' => 'Accommodations',
                    'name_fr' => 'Hébergements',
                    'description_en' => 'Hotels and guesthouses',
                    'description_fr' => 'Hôtels et maisons d\'hôtes',
                    'image' => null,
                    'link' => '/listings?type=accommodation',
                ],
                [
                    'id' => 'event',
                    'display_order' => 3,
                    'name_en' => 'Events',
                    'name_fr' => 'Événements',
                    'description_en' => 'Festivals, workshops and more',
                    'description_fr' => 'Festivals, ateliers et plus',
                    'image' => null,
                    'link' => '/listings?type=event',
                ],
            ],

            // Blog Section
            'blog_section_enabled' => true,
            'blog_section_title' => [
                'en' => 'Latest from the Blog',
                'fr' => 'Dernières Actualités',
            ],
            'blog_section_subtitle' => [
                'en' => 'Travel tips, stories, and inspiration from Djerba',
                'fr' => 'Conseils de voyage, récits et inspiration de Djerba',
            ],
            'blog_section_post_limit' => 3,

            // Featured Packages Section
            'featured_packages_enabled' => true,
            'featured_packages_title' => [
                'en' => 'Upcoming Adventures',
                'fr' => 'Aventures à Venir',
            ],
            'featured_packages_subtitle' => [
                'en' => 'Book your next unforgettable experience',
                'fr' => 'Réservez votre prochaine expérience inoubliable',
            ],
            'featured_packages_limit' => 3,

            // Custom Experience CTA Section
            'custom_experience_enabled' => true,
            'custom_experience_title' => [
                'en' => 'Create Your Perfect Adventure',
                'fr' => 'Créez Votre Aventure Sur Mesure',
            ],
            'custom_experience_description' => [
                'en' => 'Tell us your dream trip and our team will design a personalized experience just for you.',
                'fr' => 'Décrivez-nous votre voyage de rêve et notre équipe concevra une expérience personnalisée rien que pour vous.',
            ],
            'custom_experience_button_text' => [
                'en' => 'Start Planning',
                'fr' => 'Commencer la Planification',
            ],
            'custom_experience_link' => '/custom-trip',

            // Newsletter Section
            'newsletter_enabled' => true,
            'newsletter_title' => [
                'en' => 'Stay Updated',
                'fr' => 'Restez Informé',
            ],
            'newsletter_subtitle' => [
                'en' => 'Get the latest deals and travel tips delivered to your inbox',
                'fr' => 'Recevez les dernières offres et conseils de voyage dans votre boîte mail',
            ],
            'newsletter_button_text' => [
                'en' => 'Subscribe',
                'fr' => 'S\'abonner',
            ],

            // About Page - Hero
            'about_hero_title' => [
                'en' => 'About Djerba Fun',
                'fr' => 'À Propos de Djerba Fun',
            ],
            'about_hero_subtitle' => [
                'en' => 'Discover authentic experiences on the island of dreams',
                'fr' => 'Découvrez des expériences authentiques sur l\'île de rêve',
            ],
            'about_hero_tagline' => [
                'en' => 'Your Mediterranean Adventure Awaits',
                'fr' => 'Votre Aventure Méditerranéenne Vous Attend',
            ],

            // About Page - Story
            'about_story_heading' => [
                'en' => 'The Adventurer',
                'fr' => 'L\'Aventurier',
            ],
            'about_story_intro' => [
                'en' => 'Born from a passion for sharing the hidden treasures of Djerba.',
                'fr' => 'Né d\'une passion pour partager les trésors cachés de Djerba.',
            ],
            'about_story_text_1' => [
                'en' => 'Djerba Fun was created to help travelers discover the authentic beauty of Djerba island.',
                'fr' => 'Djerba Fun a été créé pour aider les voyageurs à découvrir la beauté authentique de l\'île de Djerba.',
            ],
            'about_story_text_2' => [
                'en' => 'We work with local guides and partners to create unforgettable experiences.',
                'fr' => 'Nous travaillons avec des guides et partenaires locaux pour créer des expériences inoubliables.',
            ],

            // About Page - Founder
            'about_founder_name' => 'Seif Ben Helel',
            'about_founder_story' => [
                'en' => 'Seif discovered his love for adventure on the beaches of Djerba. After years of exploring the island, he decided to share its magic with the world.',
                'fr' => 'Seif a découvert son amour pour l\'aventure sur les plages de Djerba. Après des années d\'exploration de l\'île, il a décidé de partager sa magie avec le monde.',
            ],
            'about_founder_quote' => [
                'en' => 'Every journey starts with a single step into the unknown.',
                'fr' => 'Chaque voyage commence par un premier pas vers l\'inconnu.',
            ],

            // About Page - Team
            'about_team_title' => [
                'en' => 'Our Team',
                'fr' => 'Notre Équipe',
            ],
            'about_team_description' => [
                'en' => 'A dedicated team of local experts passionate about sharing the best of Djerba.',
                'fr' => 'Une équipe dévouée d\'experts locaux passionnés par le partage du meilleur de Djerba.',
            ],

            // About Page - Impact
            'about_impact_text' => [
                'en' => '1% of our revenue supports local community initiatives and environmental conservation.',
                'fr' => '1% de nos revenus soutient les initiatives communautaires locales et la préservation de l\'environnement.',
            ],

            // About Page - Commitments (JSON array)
            'about_commitments' => [
                [
                    'icon' => 'sustainable',
                    'title_en' => 'Sustainable Travel',
                    'title_fr' => 'Tourisme Responsable',
                    'description_en' => 'We prioritize eco-friendly practices and support local communities.',
                    'description_fr' => 'Nous privilégions les pratiques écologiques et soutenons les communautés locales.',
                ],
                [
                    'icon' => 'active',
                    'title_en' => 'Active Lifestyle',
                    'title_fr' => 'Style de Vie Actif',
                    'description_en' => 'Adventures that keep you moving and engaged with nature.',
                    'description_fr' => 'Des aventures qui vous gardent actif et connecté à la nature.',
                ],
                [
                    'icon' => 'immersion',
                    'title_en' => 'Local Immersion',
                    'title_fr' => 'Immersion Locale',
                    'description_en' => 'Experience Djerba like a local, beyond the tourist paths.',
                    'description_fr' => 'Vivez Djerba comme un local, au-delà des sentiers touristiques.',
                ],
                [
                    'icon' => 'passion',
                    'title_en' => 'Passion & Expertise',
                    'title_fr' => 'Passion & Expertise',
                    'description_en' => 'Our team brings years of local knowledge and enthusiasm.',
                    'description_fr' => 'Notre équipe apporte des années de connaissances locales et d\'enthousiasme.',
                ],
            ],

            // About Page - Partners (empty by default, populated via admin)
            'about_partners' => [],

            // About Page - Initiatives (empty by default, populated via admin)
            'about_initiatives' => [],
        ]);

        $this->command->info('Platform settings seeded successfully!');
    }
}
