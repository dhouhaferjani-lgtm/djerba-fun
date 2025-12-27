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
            // Platform Identity
            'platform_name' => [
                'en' => 'Go Adventure',
                'fr' => 'Go Aventure',
                'ar' => 'مغامرة',
            ],
            'tagline' => [
                'en' => 'Discover Your Next Adventure',
                'fr' => 'Découvrez Votre Prochaine Aventure',
                'ar' => 'اكتشف مغامرتك القادمة',
            ],
            'description' => [
                'en' => 'The premier marketplace for tours, activities, and unique experiences. Book your next adventure today!',
                'fr' => 'La première place de marché pour les visites, activités et expériences uniques. Réservez votre prochaine aventure dès aujourd\'hui!',
                'ar' => 'السوق الرائد للجولات والأنشطة والتجارب الفريدة. احجز مغامرتك القادمة اليوم!',
            ],
            'primary_domain' => 'goadventure.com',
            'api_url' => env('APP_URL', 'http://localhost:8000'),
            'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),

            // SEO & Metadata
            'meta_title' => [
                'en' => 'Go Adventure - Tours, Activities & Unique Experiences',
                'fr' => 'Go Aventure - Tours, Activités & Expériences Uniques',
                'ar' => 'مغامرة - جولات وأنشطة وتجارب فريدة',
            ],
            'meta_description' => [
                'en' => 'Discover and book amazing tours, outdoor activities, and unique experiences. Your adventure starts here!',
                'fr' => 'Découvrez et réservez des visites incroyables, des activités de plein air et des expériences uniques.',
                'ar' => 'اكتشف واحجز جولات مذهلة وأنشطة خارجية وتجارب فريدة.',
            ],
            'keywords' => ['tours', 'activities', 'adventures', 'travel', 'experiences', 'booking'],
            'author' => 'Go Adventure Team',
            'organization_type' => 'TravelAgency',
            'founded_year' => 2024,

            // Contact Information
            'support_email' => 'support@goadventure.com',
            'general_email' => 'hello@goadventure.com',
            'phone_number' => '+1 (555) 123-4567',
            'whatsapp_number' => '+15551234567',
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                'thursday' => ['open' => '09:00', 'close' => '18:00'],
                'friday' => ['open' => '09:00', 'close' => '18:00'],
                'saturday' => ['open' => '10:00', 'close' => '16:00'],
                'sunday' => null,
            ],

            // Physical Address
            'address_street' => '123 Adventure Way',
            'address_city' => 'San Francisco',
            'address_region' => 'California',
            'address_postal_code' => '94102',
            'address_country' => 'US',
            'google_maps_url' => 'https://maps.google.com/?q=San+Francisco,+CA',

            // Social Media
            'social_facebook' => 'https://facebook.com/goadventure',
            'social_instagram' => 'https://instagram.com/goadventure',
            'social_twitter' => 'https://twitter.com/goadventure',
            'social_linkedin' => 'https://linkedin.com/company/goadventure',
            'social_youtube' => 'https://youtube.com/@goadventure',
            'social_tiktok' => null,

            // Email Settings
            'email_from_name' => 'Go Adventure',
            'email_from_address' => 'noreply@goadventure.com',
            'email_reply_to' => 'support@goadventure.com',
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

            // Localization
            'default_locale' => 'en',
            'available_locales' => ['en', 'fr', 'ar'],
            'fallback_locale' => 'en',
            'rtl_locales' => ['ar'],
            'date_format' => 'MMMM d, yyyy',
            'time_format' => 'h:mm a',
            'timezone' => 'UTC',
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
