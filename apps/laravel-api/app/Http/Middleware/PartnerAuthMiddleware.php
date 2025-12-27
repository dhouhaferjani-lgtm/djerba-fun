<?php

namespace App\Http\Middleware;

use App\Services\PartnerAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PartnerAuthMiddleware
{
    public function __construct(
        protected PartnerAuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Partner-Key');
        $apiSecret = $request->header('X-Partner-Secret');

        if (! $apiKey || ! $apiSecret) {
            return response()->json([
                'error' => 'Missing partner credentials',
                'message' => 'X-Partner-Key and X-Partner-Secret headers are required',
            ], 401);
        }

        $partner = $this->authService->authenticate($apiKey, $apiSecret);

        if (! $partner) {
            return response()->json([
                'error' => 'Invalid partner credentials',
                'message' => 'The provided partner credentials are invalid or the partner is inactive',
            ], 401);
        }

        // Check rate limit
        if ($this->authService->hasExceededRateLimit($partner)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'You have exceeded your rate limit',
                'retry_after' => $this->authService->getRateLimitResetTime() - time(),
            ], 429)
                ->header('X-RateLimit-Limit', $partner->rate_limit)
                ->header('X-RateLimit-Remaining', 0)
                ->header('X-RateLimit-Reset', $this->authService->getRateLimitResetTime())
                ->header('Retry-After', $this->authService->getRateLimitResetTime() - time());
        }

        // Increment rate limit
        $this->authService->incrementRateLimit($partner);

        // Add rate limit headers
        $remaining = $this->authService->getRemainingRateLimit($partner);
        $request->attributes->set('partner', $partner);
        $request->attributes->set('rate_limit_remaining', $remaining);

        // Update last used timestamp
        $partner->recordUsage();

        $response = $next($request);

        // Add rate limit headers to response
        return $response
            ->header('X-RateLimit-Limit', $partner->rate_limit)
            ->header('X-RateLimit-Remaining', $remaining)
            ->header('X-RateLimit-Reset', $this->authService->getRateLimitResetTime());
    }
}
