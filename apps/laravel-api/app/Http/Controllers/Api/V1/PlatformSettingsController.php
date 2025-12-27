<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlatformSettingsController extends Controller
{
    public function __construct(
        private readonly PlatformSettingsService $settingsService
    ) {}

    /**
     * Get public platform settings.
     *
     * This endpoint returns non-sensitive settings for frontend use.
     * Cached for performance.
     *
     * @unauthenticated
     */
    public function index(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);
        $settings = $this->settingsService->getPublicSettings($locale);

        return response()->json([
            'data' => $settings,
            'meta' => [
                'locale' => $locale,
                'cached_at' => now()->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get settings for schema.org JSON-LD.
     *
     * Returns structured data for search engines.
     *
     * @unauthenticated
     */
    public function schema(Request $request): JsonResponse
    {
        $locale = $this->getLocale($request);
        $schema = $this->settingsService->getSchemaOrgData($locale);

        return response()->json($schema);
    }

    /**
     * Get locale from request.
     */
    private function getLocale(Request $request): string
    {
        $locale = $request->header('Accept-Language', $request->query('locale', config('app.locale')));

        // Normalize locale (e.g., 'en-US' -> 'en')
        if (strlen($locale) > 2) {
            $locale = substr($locale, 0, 2);
        }

        // Validate against available locales
        $available = ['en', 'fr', 'ar'];

        if (! in_array($locale, $available)) {
            $locale = 'en';
        }

        return $locale;
    }
}
