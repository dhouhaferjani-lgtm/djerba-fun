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
     */
    public function handle(Request $request, Closure $next): Response
    {
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
