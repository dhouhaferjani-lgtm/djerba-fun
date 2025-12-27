<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
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
    ];

    /**
     * Encrypted attributes (stored encrypted, decrypted on access).
     */
    protected array $encryptedFields = [
        'google_maps_api_key',
        'sentry_dsn',
        'exchange_rate_api_key',
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

        // Exchange Rates & PPP
        'exchange_rate_api_key',
        'ppp_factor_eur',
        'ppp_factor_usd',
        'ppp_factor_gbp',

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

            // Integers
            'hold_duration_minutes' => 'integer',
            'hold_warning_minutes' => 'integer',
            'auto_cancel_hours' => 'integer',
            'data_retention_days' => 'integer',
            'minimum_age_requirement' => 'integer',
            'vendor_payout_delay_days' => 'integer',
            'founded_year' => 'integer',
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
    }

    /**
     * Get the singleton instance (cached).
     */
    public static function instance(): self
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return self::firstOrCreate([]);
        });
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
