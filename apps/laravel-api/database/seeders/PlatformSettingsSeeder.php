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
            'primary_domain' => 'djerbafun.com',
            'api_url' => env('APP_URL', 'http://localhost:8000'),
            'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

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
            'support_email' => 'support@djerba.fun',
            'general_email' => 'contact@djerba.fun',
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
            'email_from_address' => 'noreply@djerba.fun',
            'email_reply_to' => 'support@djerba.fun',
            'email_terms_url' => '/terms',
            'email_privacy_url' => '/privacy',

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
            'terms_url' => '/terms',
            'privacy_url' => '/privacy',
            'cookie_policy_url' => '/cookies',
            'refund_policy_url' => '/refund-policy',
            'data_deletion_policy_url' => '/data-deletion',
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
        ]);

        $this->command->info('Platform settings seeded successfully!');
    }
}
