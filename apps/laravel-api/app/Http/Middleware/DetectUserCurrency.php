<?php

namespace App\Http\Middleware;

use App\Services\GeoPricingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectUserCurrency
{
    public function __construct(
        protected GeoPricingService $geoPricingService
    ) {}

    /**
     * Handle an incoming request.
     *
     * Detects the user's currency based on their location and stores it
     * in the request attributes for use throughout the application.
     *
     * Priority:
     * 1. X-User-Currency header (from client-side cookie, ensures SSR consistency)
     * 2. IP-based geolocation detection
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Priority 1: Check for explicit currency preference from client
        // This header is sent by Next.js server-side requests using the cookie set by client-side pages
        // This ensures consistent currency between list page (client-side) and detail page (server-side)
        $clientCurrency = $request->header('X-User-Currency');
        if ($clientCurrency && in_array($clientCurrency, ['TND', 'EUR'], true)) {
            $request->attributes->set('user_currency', $clientCurrency);
            $request->attributes->set('currency_source', 'client_header');

            return $next($request);
        }

        // Priority 2: Detect from user profile or IP geolocation
        $user = $request->user();
        $currency = $this->geoPricingService->detectUserCurrency($request, $user);

        // Store the detected currency in request attributes
        $request->attributes->set('user_currency', $currency);

        // Also store detection metadata for debugging
        if (config('app.debug')) {
            $metadata = $this->geoPricingService->detectCurrencyWithMetadata($request, $user);
            $request->attributes->set('currency_detection_metadata', $metadata);
        }

        return $next($request);
    }
}
