<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlatformSettings;

class PlatformSettingsService
{
    protected ?PlatformSettings $settings = null;

    protected bool $withMedia = false;

    /**
     * Get settings instance (cached).
     */
    public function settings(): PlatformSettings
    {
        if ($this->settings === null) {
            $this->settings = PlatformSettings::instance();

            // If media is needed, load it fresh to ensure we have current media URLs
            if ($this->withMedia) {
                $this->settings->load('media');
            }
        }

        return $this->settings;
    }

    /**
     * Get settings with media loaded (for public API responses).
     */
    public function settingsWithMedia(): PlatformSettings
    {
        // Always get fresh from DB to ensure media is current
        // Cannot rely on cached instance for media URLs
        return PlatformSettings::with('media')->firstOrCreate([]);
    }

    /**
     * Get a single setting value.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings()->$key ?? $default;
    }

    /**
     * Get translatable setting for current or specified locale.
     */
    public function getTranslated(string $key, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        return $this->settings()->getTranslation($key, $locale);
    }

    /**
     * Check if a feature is enabled.
     */
    public function featureEnabled(string $feature): bool
    {
        return $this->settings()->isFeatureEnabled($feature);
    }

    /**
     * Get public settings for frontend (non-sensitive data only).
     *
     * Uses fresh settings with media loaded to ensure current media URLs are returned.
     */
    public function getPublicSettings(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        // IMPORTANT: Use fresh settings with media loaded for public API
        // Cached instances may have stale or missing media URLs
        $s = $this->settingsWithMedia();

        return [
            'platform' => [
                'name' => $s->getTranslation('platform_name', $locale),
                'tagline' => $s->getTranslation('tagline', $locale),
                'description' => $s->getTranslation('description', $locale),
                'domain' => $s->primary_domain,
                'frontendUrl' => $s->frontend_url,
            ],
            'branding' => [
                'logoLight' => $s->logo_light_url,
                'logoDark' => $s->logo_dark_url,
                'favicon' => $s->favicon_url,
                'ogImage' => $s->og_image_url,
                'appleTouchIcon' => $s->apple_touch_icon_url,
                'heroBanner' => $s->hero_banner_url,
                'brandPillar1' => $s->brand_pillar_1_url,
                'brandPillar2' => $s->brand_pillar_2_url,
                'brandPillar3' => $s->brand_pillar_3_url,
            ],
            'seo' => [
                'metaTitle' => $s->getTranslation('meta_title', $locale),
                'metaDescription' => $s->getTranslation('meta_description', $locale),
                'keywords' => $s->keywords,
                'author' => $s->author,
                'organizationType' => $s->organization_type,
                'foundedYear' => $s->founded_year,
            ],
            'contact' => [
                'supportEmail' => $s->support_email,
                'generalEmail' => $s->general_email,
                'phone' => $s->phone_number,
                'whatsapp' => $s->whatsapp_number,
                'businessHours' => $s->business_hours,
            ],
            'address' => [
                'street' => $s->address_street,
                'city' => $s->address_city,
                'region' => $s->address_region,
                'postalCode' => $s->address_postal_code,
                'country' => $s->address_country,
                'googleMapsUrl' => $s->google_maps_url,
                'full' => $s->full_address,
            ],
            'social' => $s->social_links,
            'localization' => [
                'defaultLocale' => $s->default_locale,
                'availableLocales' => $s->available_locales,
                'fallbackLocale' => $s->fallback_locale,
                'rtlLocales' => $s->rtl_locales,
                'dateFormat' => $s->date_format,
                'timeFormat' => $s->time_format,
                'timezone' => $s->timezone,
                'weekStartsOn' => $s->week_starts_on,
            ],
            'features' => [
                'reviews' => $s->enable_reviews,
                'wishlists' => $s->enable_wishlists,
                'giftCards' => $s->enable_gift_cards,
                'loyaltyProgram' => $s->enable_loyalty_program,
                'blog' => $s->enable_blog,
                'instantBooking' => $s->enable_instant_booking,
                'requestToBook' => $s->enable_request_to_book,
                'groupBookings' => $s->enable_group_bookings,
                'customPackages' => $s->enable_custom_packages,
            ],
            'booking' => [
                'holdDurationMinutes' => $s->hold_duration_minutes,
                'holdWarningMinutes' => $s->hold_warning_minutes,
                'defaultCurrency' => $s->default_currency,
                'enabledCurrencies' => $s->enabled_currencies,
                'minBookingAmount' => (float) $s->min_booking_amount,
                'maxBookingAmount' => (float) $s->max_booking_amount,
            ],
            'legal' => [
                'termsUrl' => $s->terms_url,
                'privacyUrl' => $s->privacy_url,
                'cookiePolicyUrl' => $s->cookie_policy_url,
                'refundPolicyUrl' => $s->refund_policy_url,
                'cookieConsentEnabled' => $s->cookie_consent_enabled,
                'gdprModeEnabled' => $s->gdpr_mode_enabled,
                'minimumAgeRequirement' => $s->minimum_age_requirement,
            ],
            'analytics' => [
                // Only non-sensitive IDs for frontend tracking scripts
                'ga4MeasurementId' => $s->ga4_measurement_id,
                'gtmContainerId' => $s->gtm_container_id,
                'facebookPixelId' => $s->facebook_pixel_id,
                'hotjarSiteId' => $s->hotjar_site_id,
                'plausibleDomain' => $s->plausible_domain,
            ],
            'eventOfYear' => [
                'enabled' => $s->event_of_year_enabled ?? true,
                'tag' => $s->getTranslation('event_of_year_tag', $locale) ?: 'Event of the Year',
                'title' => $s->getTranslation('event_of_year_title', $locale),
                'description' => $s->getTranslation('event_of_year_description', $locale),
                'link' => $s->event_of_year_link,
                'image' => $s->event_of_year_image_url,
            ],
            'hero' => [
                'title' => $s->getTranslation('hero_title', $locale),
                'subtitle' => $s->getTranslation('hero_subtitle', $locale),
            ],
            'brandPillars' => [
                'pillar1' => [
                    'title' => $s->getTranslation('pillar_1_title', $locale),
                    'description' => $s->getTranslation('pillar_1_description', $locale),
                ],
                'pillar2' => [
                    'title' => $s->getTranslation('pillar_2_title', $locale),
                    'description' => $s->getTranslation('pillar_2_description', $locale),
                ],
                'pillar3' => [
                    'title' => $s->getTranslation('pillar_3_title', $locale),
                    'description' => $s->getTranslation('pillar_3_description', $locale),
                ],
            ],
        ];
    }

    /**
     * Get settings for JSON-LD schema.org.
     *
     * Uses fresh settings with media loaded to ensure logo URL is current.
     */
    public function getSchemaOrgData(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        // Use fresh settings with media for schema.org logo
        $s = $this->settingsWithMedia();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => $s->organization_type ?? 'TravelAgency',
            'name' => $s->getTranslation('platform_name', $locale),
            'description' => $s->getTranslation('description', $locale),
            'url' => $s->frontend_url,
            'logo' => $s->logo_light_url,
        ];

        if ($s->founded_year) {
            $schema['foundingDate'] = "{$s->founded_year}-01-01";
        }

        if ($s->address_street || $s->address_city) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $s->address_street,
                'addressLocality' => $s->address_city,
                'addressRegion' => $s->address_region,
                'postalCode' => $s->address_postal_code,
                'addressCountry' => $s->address_country,
            ];
        }

        if ($s->phone_number || $s->support_email) {
            $schema['contactPoint'] = [
                '@type' => 'ContactPoint',
                'telephone' => $s->phone_number,
                'email' => $s->support_email,
                'contactType' => 'customer support',
            ];
        }

        $socialLinks = array_values($s->social_links);

        if (! empty($socialLinks)) {
            $schema['sameAs'] = $socialLinks;
        }

        return $schema;
    }

    /**
     * Get email configuration.
     */
    public function getEmailConfig(): array
    {
        $s = $this->settings();

        return [
            'from_name' => $s->email_from_name ?? $s->getTranslation('platform_name', 'en'),
            'from_address' => $s->email_from_address ?? $s->support_email,
            'reply_to' => $s->email_reply_to ?? $s->support_email,
            'terms_url' => $s->email_terms_url ?? $s->terms_url,
            'privacy_url' => $s->email_privacy_url ?? $s->privacy_url,
        ];
    }

    /**
     * Get vendor settings.
     */
    public function getVendorSettings(): array
    {
        $s = $this->settings();

        return [
            'autoApprove' => $s->vendor_auto_approve,
            'requireKyc' => $s->vendor_require_kyc,
            'kycDocumentTypes' => $s->vendor_kyc_document_types,
            'commissionRate' => (float) $s->vendor_commission_rate,
            'payoutFrequency' => $s->vendor_payout_frequency,
            'payoutMinimum' => (float) $s->vendor_payout_minimum,
            'payoutCurrency' => $s->vendor_payout_currency,
            'payoutDelayDays' => $s->vendor_payout_delay_days,
        ];
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): void
    {
        PlatformSettings::clearCache();
        $this->settings = null;
    }
}
