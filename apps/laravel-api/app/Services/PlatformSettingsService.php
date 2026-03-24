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
        $settings = PlatformSettings::with('media')->first();

        if (! $settings) {
            $settings = PlatformSettings::create([]);
            $settings->load('media');
        }

        return $settings;
    }

    /**
     * Get homepage section configuration.
     *
     * Returns sections in order with enabled flag.
     * Falls back to default order if not configured (backward compatible).
     *
     * @return array<array{id: string, enabled: bool, order: int}>
     */
    public function getHomepageSections(): array
    {
        $s = $this->settingsWithMedia();
        $stored = $s->homepage_sections['sections'] ?? null;

        // Default order (backward compatible with current hardcoded order in page.tsx)
        $defaultSections = [
            ['id' => 'hero', 'enabled' => true, 'order' => 0],
            ['id' => 'marketing_mosaic', 'enabled' => true, 'order' => 1],
            ['id' => 'featured_packages', 'enabled' => true, 'order' => 2],
            ['id' => 'promo_banner', 'enabled' => true, 'order' => 3],
            ['id' => 'experience_categories', 'enabled' => true, 'order' => 4],
            ['id' => 'testimonials', 'enabled' => true, 'order' => 5],
            ['id' => 'destinations', 'enabled' => true, 'order' => 6],
            ['id' => 'cta', 'enabled' => true, 'order' => 7],
            ['id' => 'blog', 'enabled' => true, 'order' => 8],
            ['id' => 'newsletter', 'enabled' => true, 'order' => 9],
        ];

        if (! $stored) {
            return $defaultSections;
        }

        // Sort by order and return
        return collect($stored)
            ->sortBy('order')
            ->values()
            ->toArray();
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
                'heroBannerIsVideo' => str_starts_with($s->getFirstMedia('hero_banner')?->mime_type ?? '', 'video/'),
                'heroBannerThumbnail' => $s->hero_banner_thumbnail_url,
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
                'enabledPaymentMethods' => $this->mapPaymentMethods($s->enabled_payment_methods ?? []),
                'bankTransfer' => [
                    'bankName' => $s->bank_transfer_bank_name,
                    'accountHolder' => $s->bank_transfer_account_holder,
                    'iban' => $s->bank_transfer_iban,
                    'swiftBic' => $s->bank_transfer_swift_bic,
                    'accountNumber' => $s->bank_transfer_account_number,
                    'instructions' => $s->bank_transfer_instructions,
                ],
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
            'featuredDestinations' => collect($s->featured_destinations ?? [])->map(function ($dest) {
                // Convert relative image paths to full URLs
                if (! empty($dest['image']) && ! str_starts_with($dest['image'], 'http')) {
                    $dest['image'] = asset('storage/'.$dest['image']);
                }

                // Convert gallery image paths to full URLs
                if (! empty($dest['gallery']) && is_array($dest['gallery'])) {
                    $dest['gallery'] = array_map(function ($item) {
                        if (! empty($item['image']) && ! str_starts_with($item['image'], 'http')) {
                            $item['image'] = asset('storage/'.$item['image']);
                        }

                        return $item;
                    }, $dest['gallery']);
                }

                return $dest;
            })->values()->toArray(),
            'testimonials' => collect($s->testimonials ?? [])->map(function ($testimonial) {
                if (! empty($testimonial['photo']) && ! str_starts_with($testimonial['photo'], 'http')) {
                    $testimonial['photo'] = asset('storage/'.$testimonial['photo']);
                }

                return $testimonial;
            })->values()->toArray(),

            // CMS Section: Experience Categories
            'experienceCategories' => [
                'enabled' => $s->experience_categories_enabled ?? true,
                'title' => $s->getTranslation('experience_categories_title', $locale),
                'subtitle' => $s->getTranslation('experience_categories_subtitle', $locale),
                'categories' => collect($s->experience_categories ?? [])->sortBy('display_order')->map(function ($category) use ($locale) {
                    // Convert relative image path to full URL
                    $imageUrl = null;
                    if (! empty($category['image'])) {
                        if (str_starts_with($category['image'], 'http')) {
                            $imageUrl = $category['image'];
                        } else {
                            $imageUrl = asset('storage/' . $category['image']);
                        }
                    }

                    return [
                        'id' => $category['id'] ?? null,
                        'name' => $locale === 'fr'
                            ? ($category['name_fr'] ?? $category['name_en'] ?? '')
                            : ($category['name_en'] ?? ''),
                        'description' => $locale === 'fr'
                            ? ($category['description_fr'] ?? $category['description_en'] ?? null)
                            : ($category['description_en'] ?? null),
                        'image' => $imageUrl,
                        'link' => $category['link'] ?? null,
                        'displayOrder' => $category['display_order'] ?? 0,
                    ];
                })->values()->toArray(),
            ],

            // CMS Section: Blog
            'blogSection' => [
                'enabled' => $s->blog_section_enabled ?? true,
                'title' => $s->getTranslation('blog_section_title', $locale),
                'subtitle' => $s->getTranslation('blog_section_subtitle', $locale),
                'postLimit' => $s->blog_section_post_limit ?? 3,
            ],

            // CMS Section: Featured Packages
            'featuredPackages' => [
                'enabled' => $s->featured_packages_enabled ?? true,
                'title' => $s->getTranslation('featured_packages_title', $locale),
                'subtitle' => $s->getTranslation('featured_packages_subtitle', $locale),
                'limit' => $s->featured_packages_limit ?? 3,
            ],

            // CMS Section: Custom Experience CTA
            'customExperience' => [
                'enabled' => $s->custom_experience_enabled ?? true,
                'title' => $s->getTranslation('custom_experience_title', $locale),
                'description' => $s->getTranslation('custom_experience_description', $locale),
                'buttonText' => $s->getTranslation('custom_experience_button_text', $locale),
                'link' => $s->custom_experience_link,
            ],

            // CMS Section: Newsletter
            'newsletter' => [
                'enabled' => $s->newsletter_enabled ?? true,
                'title' => $s->getTranslation('newsletter_title', $locale),
                'subtitle' => $s->getTranslation('newsletter_subtitle', $locale),
                'buttonText' => $s->getTranslation('newsletter_button_text', $locale),
            ],

            // Homepage Sections (order and visibility for homepage builder)
            'homepageSections' => $this->getHomepageSections(),

            // CMS Section: About Page
            'about' => [
                'hero' => [
                    'title' => $s->getTranslation('about_hero_title', $locale),
                    'subtitle' => $s->getTranslation('about_hero_subtitle', $locale),
                    'tagline' => $s->getTranslation('about_hero_tagline', $locale),
                    'image' => $s->about_hero_image_url,
                ],
                'story' => [
                    'heading' => $s->getTranslation('about_story_heading', $locale),
                    'intro' => $s->getTranslation('about_story_intro', $locale),
                    'text1' => $s->getTranslation('about_story_text_1', $locale),
                    'text2' => $s->getTranslation('about_story_text_2', $locale),
                ],
                'founder' => [
                    'name' => $s->about_founder_name,
                    'photo' => $s->about_founder_photo_url,
                    'story' => $s->getTranslation('about_founder_story', $locale),
                    'quote' => $s->getTranslation('about_founder_quote', $locale),
                ],
                'team' => [
                    'title' => $s->getTranslation('about_team_title', $locale),
                    'description' => $s->getTranslation('about_team_description', $locale),
                ],
                'impactText' => $s->getTranslation('about_impact_text', $locale),
                'commitments' => collect($s->about_commitments ?? [])->map(function ($commitment) use ($locale) {
                    return [
                        'icon' => $commitment['icon'] ?? null,
                        'title' => $locale === 'fr' ? ($commitment['title_fr'] ?? $commitment['title_en'] ?? '') : ($commitment['title_en'] ?? ''),
                        'description' => $locale === 'fr' ? ($commitment['description_fr'] ?? $commitment['description_en'] ?? '') : ($commitment['description_en'] ?? ''),
                    ];
                })->values()->toArray(),
                'partners' => collect($s->about_partners ?? [])->map(function ($partner) {
                    $logo = $partner['logo'] ?? null;
                    if ($logo && ! str_starts_with($logo, 'http')) {
                        $logo = asset('storage/'.$logo);
                    }

                    return [
                        'name' => $partner['name'] ?? null,
                        'logo' => $logo,
                    ];
                })->values()->toArray(),
                'initiatives' => collect($s->about_initiatives ?? [])->map(function ($initiative) use ($locale) {
                    $image = $initiative['image'] ?? null;
                    if ($image && ! str_starts_with($image, 'http')) {
                        $image = asset('storage/'.$image);
                    }

                    return [
                        'image' => $image,
                        'alt' => $locale === 'fr' ? ($initiative['alt_fr'] ?? $initiative['alt_en'] ?? '') : ($initiative['alt_en'] ?? ''),
                    ];
                })->values()->toArray(),
                // Initiatives Text Section (the lime green box with bullet points)
                'initiativesText' => [
                    'title' => $s->getTranslation('about_initiatives_title', $locale),
                    'description' => $s->getTranslation('about_initiatives_description', $locale),
                    'bullets' => collect($s->about_initiatives_bullets ?? [])->map(function ($bullet) use ($locale) {
                        return $locale === 'fr'
                            ? ($bullet['text_fr'] ?? $bullet['text_en'] ?? '')
                            : ($bullet['text_en'] ?? '');
                    })->values()->toArray(),
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

    /**
     * Map stored payment method values to frontend-compatible names.
     *
     * Handles backward compatibility: old admin values (card, bank_transfer)
     * are mapped to the correct frontend values (click_to_pay, offline).
     *
     * @param  array<string>  $methods
     * @return array<string>
     */
    protected function mapPaymentMethods(array $methods): array
    {
        $map = [
            'card' => 'click_to_pay',
            'bank_transfer' => 'offline',
            'offline' => 'offline',
            'cash' => 'cash',
            'click_to_pay' => 'click_to_pay',
        ];

        return array_values(array_unique(array_filter(
            array_map(fn ($m) => $map[$m] ?? $m, $methods)
        )));
    }
}
