<?php

namespace App\Http\Middleware;

use App\Services\AgentAuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AgentAuthMiddleware
{
    public function __construct(
        protected AgentAuthService $authService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Agent-Key');
        $apiSecret = $request->header('X-Agent-Secret');

        if (!$apiKey || !$apiSecret) {
            return response()->json([
                'error' => 'Missing agent credentials',
                'message' => 'X-Agent-Key and X-Agent-Secret headers are required',
            ], 401);
        }

        $agent = $this->authService->authenticate($apiKey, $apiSecret);

        if (!$agent) {
            return response()->json([
                'error' => 'Invalid agent credentials',
                'message' => 'The provided agent credentials are invalid or the agent is inactive',
            ], 401);
        }

        // Check rate limit
        if ($this->authService->hasExceededRateLimit($agent)) {
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'You have exceeded your rate limit',
                'retry_after' => $this->authService->getRateLimitResetTime() - time(),
            ], 429)
            ->header('X-RateLimit-Limit', $agent->rate_limit)
            ->header('X-RateLimit-Remaining', 0)
            ->header('X-RateLimit-Reset', $this->authService->getRateLimitResetTime())
            ->header('Retry-After', $this->authService->getRateLimitResetTime() - time());
        }

        // Increment rate limit
        $this->authService->incrementRateLimit($agent);

        // Add rate limit headers
        $remaining = $this->authService->getRemainingRateLimit($agent);
        $request->attributes->set('agent', $agent);
        $request->attributes->set('rate_limit_remaining', $remaining);

        // Update last used timestamp
        $agent->recordUsage();

        $response = $next($request);

        // Add rate limit headers to response
        return $response
            ->header('X-RateLimit-Limit', $agent->rate_limit)
            ->header('X-RateLimit-Remaining', $remaining)
            ->header('X-RateLimit-Reset', $this->authService->getRateLimitResetTime());
    }
}
