<?php

declare(strict_types=1);

use App\Services\PlatformSettingsService;

if (! function_exists('platform_setting')) {
    /**
     * Get a platform setting value.
     *
     * @param  string  $key  The setting key (e.g., 'support_email', 'default_currency')
     * @param  mixed  $default  Default value if setting not found
     */
    function platform_setting(string $key, mixed $default = null): mixed
    {
        return app(PlatformSettingsService::class)->get($key, $default);
    }
}

if (! function_exists('platform_name')) {
    /**
     * Get the platform name for current or specified locale.
     *
     * @param  string|null  $locale  Locale code (e.g., 'en', 'fr', 'ar')
     */
    function platform_name(?string $locale = null): string
    {
        return app(PlatformSettingsService::class)->getTranslated('platform_name', $locale)
            ?? config('app.name');
    }
}

if (! function_exists('platform_feature')) {
    /**
     * Check if a platform feature is enabled.
     *
     * @param  string  $feature  Feature name without 'enable_' prefix (e.g., 'reviews', 'wishlists')
     */
    function platform_feature(string $feature): bool
    {
        return app(PlatformSettingsService::class)->featureEnabled($feature);
    }
}

if (! function_exists('platform_url')) {
    /**
     * Get the platform frontend URL, optionally with a path.
     *
     * @param  string|null  $path  Path to append (e.g., '/listings')
     */
    function platform_url(?string $path = null): string
    {
        $baseUrl = app(PlatformSettingsService::class)->get('frontend_url', config('app.url'));

        return $path ? rtrim($baseUrl, '/') . '/' . ltrim($path, '/') : $baseUrl;
    }
}

if (! function_exists('platform_settings')) {
    /**
     * Get the platform settings service instance.
     */
    function platform_settings(): PlatformSettingsService
    {
        return app(PlatformSettingsService::class);
    }
}
