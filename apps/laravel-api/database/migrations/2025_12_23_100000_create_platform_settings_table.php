<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();

            // =================================================================
            // SECTION 1: Platform Identity (translatable stored as JSON)
            // =================================================================
            $table->json('platform_name')->nullable();        // {en: '', fr: '', ar: ''}
            $table->json('tagline')->nullable();
            $table->json('description')->nullable();
            $table->string('primary_domain')->nullable();
            $table->string('api_url')->nullable();
            $table->string('frontend_url')->nullable();

            // =================================================================
            // SECTION 2: Logo & Branding (via Spatie Media Library)
            // Media collections: logo_light, logo_dark, favicon, og_image, apple_touch_icon
            // =================================================================

            // =================================================================
            // SECTION 3: SEO & Metadata (translatable)
            // =================================================================
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('keywords')->nullable();             // Array of keywords
            $table->string('author')->nullable();
            $table->string('organization_type')->default('TravelAgency'); // schema.org
            $table->year('founded_year')->nullable();

            // =================================================================
            // SECTION 4: Contact Information
            // =================================================================
            $table->string('support_email')->nullable();
            $table->string('general_email')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->json('business_hours')->nullable();       // {mon: {open, close}, ...}

            // =================================================================
            // SECTION 5: Physical Address
            // =================================================================
            $table->string('address_street')->nullable();
            $table->string('address_city')->nullable();
            $table->string('address_region')->nullable();
            $table->string('address_postal_code')->nullable();
            $table->string('address_country', 2)->default('TN');
            $table->string('google_maps_url')->nullable();

            // =================================================================
            // SECTION 6: Social Media URLs
            // =================================================================
            $table->string('social_facebook')->nullable();
            $table->string('social_instagram')->nullable();
            $table->string('social_twitter')->nullable();
            $table->string('social_linkedin')->nullable();
            $table->string('social_youtube')->nullable();
            $table->string('social_tiktok')->nullable();

            // =================================================================
            // SECTION 7: Email Settings
            // =================================================================
            $table->string('email_from_name')->nullable();
            $table->string('email_from_address')->nullable();
            $table->string('email_reply_to')->nullable();
            $table->string('email_terms_url')->nullable();
            $table->string('email_privacy_url')->nullable();

            // =================================================================
            // SECTION 8: Payment & Commerce
            // =================================================================
            $table->string('default_currency', 3)->default('EUR');
            $table->json('enabled_currencies')->nullable();   // ['EUR', 'TND', 'USD']
            $table->decimal('platform_commission_percent', 5, 2)->default(15.00);
            $table->decimal('payment_processing_fee_percent', 5, 2)->default(2.50);
            $table->decimal('min_booking_amount', 10, 2)->default(10.00);
            $table->decimal('max_booking_amount', 10, 2)->default(50000.00);
            $table->string('default_payment_gateway')->default('mock');
            $table->json('enabled_payment_methods')->nullable(); // ['card', 'bank_transfer', 'cash']

            // Exchange Rates & PPP
            $table->text('exchange_rate_api_key')->nullable();  // Encrypted API key
            $table->decimal('ppp_factor_eur', 5, 4)->default(0.8500);
            $table->decimal('ppp_factor_usd', 5, 4)->default(0.8200);
            $table->decimal('ppp_factor_gbp', 5, 4)->default(0.8500);

            // =================================================================
            // SECTION 9: Booking Settings
            // =================================================================
            $table->integer('hold_duration_minutes')->default(15);
            $table->integer('hold_warning_minutes')->default(3);
            $table->integer('auto_cancel_hours')->default(24);
            $table->json('default_cancellation_policy')->nullable();

            // =================================================================
            // SECTION 10: Localization
            // =================================================================
            $table->string('default_locale', 5)->default('en');
            $table->json('available_locales')->nullable();    // ['en', 'fr', 'ar']
            $table->string('fallback_locale', 5)->default('en');
            $table->json('rtl_locales')->nullable();          // ['ar']
            $table->string('date_format')->default('d/m/Y');
            $table->string('time_format')->default('24h');    // '12h' or '24h'
            $table->string('timezone')->default('Africa/Tunis');
            $table->string('week_starts_on')->default('monday');

            // =================================================================
            // SECTION 11: Feature Flags
            // =================================================================
            $table->boolean('enable_reviews')->default(true);
            $table->boolean('enable_wishlists')->default(false);
            $table->boolean('enable_gift_cards')->default(false);
            $table->boolean('enable_loyalty_program')->default(false);
            $table->boolean('enable_partner_api')->default(true);
            $table->boolean('enable_blog')->default(true);
            $table->boolean('enable_instant_booking')->default(true);
            $table->boolean('enable_request_to_book')->default(false);
            $table->boolean('enable_group_bookings')->default(true);
            $table->boolean('enable_custom_packages')->default(false);

            // =================================================================
            // SECTION 12: Analytics & Tracking (some encrypted)
            // =================================================================
            $table->string('ga4_measurement_id')->nullable();
            $table->string('gtm_container_id')->nullable();
            $table->string('google_search_console_verification')->nullable();
            $table->text('google_maps_api_key')->nullable();  // Encrypted
            $table->string('facebook_pixel_id')->nullable();
            $table->string('hotjar_site_id')->nullable();
            $table->string('plausible_domain')->nullable();
            $table->text('sentry_dsn')->nullable();           // Encrypted

            // =================================================================
            // SECTION 13: Legal & Compliance
            // =================================================================
            $table->string('terms_url')->nullable();
            $table->string('privacy_url')->nullable();
            $table->string('cookie_policy_url')->nullable();
            $table->string('refund_policy_url')->nullable();
            $table->string('data_deletion_policy_url')->nullable();
            $table->boolean('cookie_consent_enabled')->default(true);
            $table->boolean('gdpr_mode_enabled')->default(true);
            $table->integer('data_retention_days')->default(730); // 2 years
            $table->integer('minimum_age_requirement')->default(18);

            // =================================================================
            // SECTION 14: Vendor Settings
            // =================================================================
            $table->boolean('vendor_auto_approve')->default(false);
            $table->boolean('vendor_require_kyc')->default(true);
            $table->json('vendor_kyc_document_types')->nullable(); // ['id_proof', 'business_license']
            $table->decimal('vendor_commission_rate', 5, 2)->default(15.00);
            $table->string('vendor_payout_frequency')->default('weekly'); // daily, weekly, monthly
            $table->decimal('vendor_payout_minimum', 10, 2)->default(50.00);
            $table->string('vendor_payout_currency', 3)->default('EUR');
            $table->integer('vendor_payout_delay_days')->default(7);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_settings');
    }
};
