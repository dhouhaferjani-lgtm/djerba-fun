<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class PlatformSettings extends Model implements HasMedia
{
    use HasTranslations;
    use InteractsWithMedia;

    protected $table = 'platform_settings';

    /**
     * Cache key for settings.
     */
    public const CACHE_KEY = 'platform_settings';

    public const CACHE_TTL = 3600; // 1 hour

    /**
     * Translatable attributes.
     */
    public array $translatable = [
        'platform_name',
        'tagline',
        'description',
        'meta_title',
        'meta_description',
        'event_of_year_tag',
        'event_of_year_title',
        'event_of_year_description',
        // Hero section text (single title with first word styled differently)
        'hero_title',
        'hero_subtitle',
        // Brand pillar text (Marketing Mosaic Section)
        'pillar_1_title',
        'pillar_1_description',
        'pillar_2_title',
        'pillar_2_description',
        'pillar_3_title',
        'pillar_3_description',
        // CMS Section: Experience Categories
        'experience_categories_title',
        'experience_categories_subtitle',
        // CMS Section: Blog
        'blog_section_title',
        'blog_section_subtitle',
        // CMS Section: Featured Packages
        'featured_packages_title',
        'featured_packages_subtitle',
        // CMS Section: Custom Experience CTA
        'custom_experience_title',
        'custom_experience_description',
        'custom_experience_button_text',
        // CMS Section: Newsletter
        'newsletter_title',
        'newsletter_subtitle',
        'newsletter_button_text',
        // CMS Section: About Page
        'about_hero_title',
        'about_hero_subtitle',
        'about_hero_tagline',
        'about_founder_story',
        'about_founder_quote',
        'about_story_heading',
        'about_story_intro',
        'about_story_text_1',
        'about_story_text_2',
        'about_team_title',
        'about_team_description',
        'about_impact_text',
    ];

    /**
     * Encrypted attributes (stored encrypted, decrypted on access).
     */
    protected array $encryptedFields = [
        'google_maps_api_key',
        'sentry_dsn',
        'exchange_rate_api_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'clicktopay_api_key',
        'clicktopay_secret_key',
    ];

    protected $fillable = [
        // Platform Identity
        'platform_name',
        'tagline',
        'description',
        'primary_domain',
        'api_url',
        'frontend_url',

        // Brand Colors
        'brand_color_primary',
        'brand_color_accent',
        'brand_color_cream',

        // SEO & Metadata
        'meta_title',
        'meta_description',
        'keywords',
        'author',
        'organization_type',
        'founded_year',

        // Contact Information
        'support_email',
        'general_email',
        'phone_number',
        'whatsapp_number',
        'business_hours',

        // Physical Address
        'address_street',
        'address_city',
        'address_region',
        'address_postal_code',
        'address_country',
        'google_maps_url',

        // Social Media
        'social_facebook',
        'social_instagram',
        'social_twitter',
        'social_linkedin',
        'social_youtube',
        'social_tiktok',

        // Email Settings
        'email_from_name',
        'email_from_address',
        'email_reply_to',
        'email_terms_url',
        'email_privacy_url',

        // Payment & Commerce
        'default_currency',
        'enabled_currencies',
        'platform_commission_percent',
        'payment_processing_fee_percent',
        'min_booking_amount',
        'max_booking_amount',
        'default_payment_gateway',
        'enabled_payment_methods',

        // Payment Gateway Settings
        'mock_gateway_enabled',
        'stripe_publishable_key',
        'stripe_secret_key',
        'stripe_webhook_secret',
        'clicktopay_merchant_id',
        'clicktopay_api_key',
        'clicktopay_secret_key',
        'clicktopay_test_mode',
        'bank_transfer_bank_name',
        'bank_transfer_account_holder',
        'bank_transfer_account_number',
        'bank_transfer_iban',
        'bank_transfer_swift_bic',
        'bank_transfer_instructions',
        'offline_payments_enabled',

        // Exchange Rates & PPP
        'exchange_rate_api_key',
        'ppp_factor_eur',
        'ppp_factor_usd',
        'ppp_factor_gbp',
        'eur_to_tnd_rate',

        // Booking Settings
        'hold_duration_minutes',
        'hold_warning_minutes',
        'auto_cancel_hours',
        'default_cancellation_policy',

        // Localization
        'default_locale',
        'available_locales',
        'fallback_locale',
        'rtl_locales',
        'date_format',
        'time_format',
        'timezone',
        'week_starts_on',

        // Feature Flags
        'enable_reviews',
        'enable_wishlists',
        'enable_gift_cards',
        'enable_loyalty_program',
        'enable_partner_api',
        'enable_blog',
        'enable_instant_booking',
        'enable_request_to_book',
        'enable_group_bookings',
        'enable_custom_packages',

        // Analytics & Tracking
        'ga4_measurement_id',
        'gtm_container_id',
        'google_search_console_verification',
        'google_maps_api_key',
        'facebook_pixel_id',
        'hotjar_site_id',
        'plausible_domain',
        'sentry_dsn',

        // Legal & Compliance
        'terms_url',
        'privacy_url',
        'cookie_policy_url',
        'refund_policy_url',
        'data_deletion_policy_url',
        'cookie_consent_enabled',
        'gdpr_mode_enabled',
        'data_retention_days',
        'minimum_age_requirement',

        // Vendor Settings
        'vendor_auto_approve',
        'vendor_require_kyc',
        'vendor_kyc_document_types',
        'vendor_commission_rate',
        'vendor_payout_frequency',
        'vendor_payout_minimum',
        'vendor_payout_currency',
        'vendor_payout_delay_days',

        // Event of the Year
        'event_of_year_title',
        'event_of_year_description',
        'event_of_year_link',
        'event_of_year_tag',
        'event_of_year_enabled',

        // Hero Section Text (Homepage)
        'hero_title',
        'hero_subtitle',

        // Brand Pillar Text (Marketing Mosaic Section)
        'pillar_1_title',
        'pillar_1_description',
        'pillar_2_title',
        'pillar_2_description',
        'pillar_3_title',
        'pillar_3_description',

        // Featured Destinations (Bento Grid Section)
        'featured_destinations',

        // Testimonials (Homepage)
        'testimonials',

        // CMS Section: Experience Categories
        'experience_categories_enabled',
        'experience_categories_title',
        'experience_categories_subtitle',

        // CMS Section: Blog
        'blog_section_enabled',
        'blog_section_title',
        'blog_section_subtitle',
        'blog_section_post_limit',

        // CMS Section: Featured Packages
        'featured_packages_enabled',
        'featured_packages_title',
        'featured_packages_subtitle',
        'featured_packages_limit',

        // CMS Section: Custom Experience CTA
        'custom_experience_enabled',
        'custom_experience_title',
        'custom_experience_description',
        'custom_experience_button_text',
        'custom_experience_link',

        // CMS Section: Newsletter
        'newsletter_enabled',
        'newsletter_title',
        'newsletter_subtitle',
        'newsletter_button_text',

        // CMS Section: About Page
        'about_hero_title',
        'about_hero_subtitle',
        'about_hero_tagline',
        'about_founder_name',
        'about_founder_story',
        'about_founder_quote',
        'about_story_heading',
        'about_story_intro',
        'about_story_text_1',
        'about_story_text_2',
        'about_team_title',
        'about_team_description',
        'about_impact_text',
        'about_commitments',
        'about_partners',
        'about_initiatives',
    ];

    protected function casts(): array
    {
        return [
            // JSON arrays
            'keywords' => 'array',
            'business_hours' => 'array',
            'enabled_currencies' => 'array',
            'enabled_payment_methods' => 'array',
            'default_cancellation_policy' => 'array',
            'available_locales' => 'array',
            'rtl_locales' => 'array',
            'vendor_kyc_document_types' => 'array',
            'featured_destinations' => 'array',
            'testimonials' => 'array',
            // CMS Section: About Page (JSON arrays)
            'about_commitments' => 'array',
            'about_partners' => 'array',
            'about_initiatives' => 'array',

            // Booleans
            'enable_reviews' => 'boolean',
            'enable_wishlists' => 'boolean',
            'enable_gift_cards' => 'boolean',
            'enable_loyalty_program' => 'boolean',
            'enable_partner_api' => 'boolean',
            'enable_blog' => 'boolean',
            'enable_instant_booking' => 'boolean',
            'enable_request_to_book' => 'boolean',
            'enable_group_bookings' => 'boolean',
            'enable_custom_packages' => 'boolean',
            'cookie_consent_enabled' => 'boolean',
            'gdpr_mode_enabled' => 'boolean',
            'vendor_auto_approve' => 'boolean',
            'vendor_require_kyc' => 'boolean',
            'mock_gateway_enabled' => 'boolean',
            'clicktopay_test_mode' => 'boolean',
            'offline_payments_enabled' => 'boolean',
            'event_of_year_enabled' => 'boolean',
            // CMS Section enabled booleans
            'experience_categories_enabled' => 'boolean',
            'blog_section_enabled' => 'boolean',
            'featured_packages_enabled' => 'boolean',
            'custom_experience_enabled' => 'boolean',
            'newsletter_enabled' => 'boolean',

            // Decimals
            'platform_commission_percent' => 'decimal:2',
            'payment_processing_fee_percent' => 'decimal:2',
            'min_booking_amount' => 'decimal:2',
            'max_booking_amount' => 'decimal:2',
            'vendor_commission_rate' => 'decimal:2',
            'vendor_payout_minimum' => 'decimal:2',
            'ppp_factor_eur' => 'decimal:4',
            'ppp_factor_usd' => 'decimal:4',
            'ppp_factor_gbp' => 'decimal:4',
            'eur_to_tnd_rate' => 'decimal:4',

            // Integers
            'hold_duration_minutes' => 'integer',
            'hold_warning_minutes' => 'integer',
            'auto_cancel_hours' => 'integer',
            'data_retention_days' => 'integer',
            'minimum_age_requirement' => 'integer',
            'vendor_payout_delay_days' => 'integer',
            'founded_year' => 'integer',
            // CMS Section integers
            'blog_section_post_limit' => 'integer',
            'featured_packages_limit' => 'integer',
        ];
    }

    /**
     * Register media collections for logos and images.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo_light')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg']);

        $this->addMediaCollection('logo_dark')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/svg+xml', 'image/webp', 'image/jpeg']);

        $this->addMediaCollection('favicon')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/x-icon', 'image/svg+xml', 'image/vnd.microsoft.icon']);

        $this->addMediaCollection('og_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);

        $this->addMediaCollection('apple_touch_icon')
            ->singleFile()
            ->acceptsMimeTypes(['image/png']);

        $this->addMediaCollection('hero_banner')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp', 'video/mp4', 'video/webm']);

        // Brand Pillar Images (Marketing Mosaic Section)
        $this->addMediaCollection('brand_pillar_1')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);

        $this->addMediaCollection('brand_pillar_2')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);

        $this->addMediaCollection('brand_pillar_3')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);

        // Event of the Year (Promo Banner)
        $this->addMediaCollection('event_of_year_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);

        // About Page Hero Image
        $this->addMediaCollection('about_hero_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);

        // About Page Founder Photo
        $this->addMediaCollection('about_founder_photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/jpeg', 'image/webp']);
    }

    /**
     * Register media conversions for generating thumbnails from videos.
     *
     * This ensures that when a video is uploaded to hero_banner,
     * a thumbnail image is automatically extracted from the first second.
     * This thumbnail serves as the poster image while the video loads,
     * ensuring alignment between poster and video content.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(1920)
            ->height(1080)
            ->extractVideoFrameAtSecond(1)
            ->performOnCollections('hero_banner')
            ->nonQueued(); // Generate synchronously to ensure it's ready immediately
    }

    /**
     * Get the singleton instance (cached).
     *
     * WARNING: Do NOT use this for Filament forms with media uploads.
     * Cached models lose their Eloquent state after serialization.
     * Use freshInstance() instead for admin operations.
     */
    public static function instance(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::firstOrCreate([]);
        });
    }

    /**
     * Get a fresh instance directly from database (not cached).
     *
     * This is required for:
     * - Filament admin pages with SpatieMediaLibraryFileUpload
     * - Any operation that modifies media collections
     * - When you need the model with current media loaded
     *
     * The fresh instance has proper Eloquent state for media library operations.
     */
    public static function freshInstance(): self
    {
        // Get or create the settings record directly from DB
        $settings = self::first();

        if (! $settings) {
            $settings = self::create([]);
        }

        // Eager load media to ensure it's available
        $settings->load('media');

        return $settings;
    }

    /**
     * Clear the settings cache.
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * Override save to clear cache.
     */
    public function save(array $options = []): bool
    {
        $saved = parent::save($options);

        if ($saved) {
            self::clearCache();
        }

        return $saved;
    }

    /**
     * Get encrypted attribute.
     */
    public function getAttribute($key): mixed
    {
        $value = parent::getAttribute($key);

        if (in_array($key, $this->encryptedFields) && $value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value; // Return as-is if decryption fails (e.g., not encrypted yet)
            }
        }

        return $value;
    }

    /**
     * Set encrypted attribute.
     */
    public function setAttribute($key, $value): static
    {
        if (in_array($key, $this->encryptedFields) && $value && ! $this->isEncrypted($value)) {
            $value = Crypt::encryptString($value);
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Check if a value is already encrypted.
     */
    protected function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    // =========================================================================
    // CONVENIENCE ACCESSORS
    // =========================================================================

    public function getLogoLightUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo_light') ?: null;
    }

    public function getLogoDarkUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo_dark') ?: null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('favicon') ?: null;
    }

    public function getOgImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('og_image') ?: null;
    }

    public function getAppleTouchIconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('apple_touch_icon') ?: null;
    }

    public function getHeroBannerUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('hero_banner') ?: null;
    }

    /**
     * Get the hero banner thumbnail URL.
     *
     * For videos: returns the auto-generated thumbnail from the first second.
     * For images: returns the image itself (no separate thumbnail needed).
     * This ensures the poster image always matches the hero content.
     *
     * Returns null if thumbnail conversion doesn't exist (e.g., FFmpeg not available),
     * allowing the frontend to use its default fallback image.
     */
    public function getHeroBannerThumbnailUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('hero_banner');

        if (! $media) {
            return null;
        }

        // If it's a video, return the thumbnail conversion only if it exists
        if (str_starts_with($media->mime_type, 'video/')) {
            // Check if thumbnail conversion was actually generated
            // (may fail if FFmpeg is not available)
            if (! $media->hasGeneratedConversion('thumbnail')) {
                return null;
            }

            return $media->getUrl('thumbnail') ?: null;
        }

        // If it's an image, return the image itself as the "thumbnail"
        return $media->getUrl();
    }

    public function getBrandPillar1UrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('brand_pillar_1') ?: null;
    }

    public function getBrandPillar2UrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('brand_pillar_2') ?: null;
    }

    public function getBrandPillar3UrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('brand_pillar_3') ?: null;
    }

    public function getEventOfYearImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('event_of_year_image') ?: null;
    }

    public function getAboutHeroImageUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('about_hero_image') ?: null;
    }

    public function getAboutFounderPhotoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('about_founder_photo') ?: null;
    }

    /**
     * Get all social media URLs as array.
     */
    public function getSocialLinksAttribute(): array
    {
        return array_filter([
            'facebook' => $this->social_facebook,
            'instagram' => $this->social_instagram,
            'twitter' => $this->social_twitter,
            'linkedin' => $this->social_linkedin,
            'youtube' => $this->social_youtube,
            'tiktok' => $this->social_tiktok,
        ]);
    }

    /**
     * Check if a feature is enabled.
     */
    public function isFeatureEnabled(string $feature): bool
    {
        $key = 'enable_' . $feature;

        return $this->$key ?? false;
    }

    /**
     * Check if a locale is RTL.
     */
    public function isRtlLocale(string $locale): bool
    {
        return in_array($locale, $this->rtl_locales ?? ['ar']);
    }

    /**
     * Get full address as string.
     */
    public function getFullAddressAttribute(): ?string
    {
        $parts = array_filter([
            $this->address_street,
            $this->address_city,
            $this->address_region,
            $this->address_postal_code,
            $this->address_country,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }
}
