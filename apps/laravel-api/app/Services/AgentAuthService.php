<?php

namespace App\Services;

use App\Models\Agent;
use Illuminate\Support\Facades\Cache;

class AgentAuthService
{
    /**
     * Authenticate an agent using API key and secret.
     */
    public function authenticate(string $apiKey, string $apiSecret): ?Agent
    {
        // Hash the API key for lookup
        $hashedKey = hash('sha256', $apiKey);

        // Try to get from cache first
        $cacheKey = "agent_auth:{$hashedKey}";

        $agent = Cache::remember($cacheKey, 300, function () use ($hashedKey) {
            return Agent::active()
                ->where('api_key', $hashedKey)
                ->first();
        });

        if (!$agent) {
            return null;
        }

        // Verify the secret
        if (!$agent->verifySecret($apiSecret)) {
            return null;
        }

        return $agent;
    }

    /**
     * Check if an agent has permission for an action.
     */
    public function checkPermission(Agent $agent, string $permission): bool
    {
        return $agent->hasPermission($permission);
    }

    /**
     * Get rate limit for an agent.
     */
    public function getRateLimit(Agent $agent): int
    {
        return $agent->rate_limit;
    }

    /**
     * Check if agent has exceeded rate limit.
     */
    public function hasExceededRateLimit(Agent $agent): bool
    {
        $key = "agent_rate_limit:{$agent->id}";
        $attempts = Cache::get($key, 0);

        return $attempts >= $agent->rate_limit;
    }

    /**
     * Increment rate limit counter.
     */
    public function incrementRateLimit(Agent $agent): void
    {
        $key = "agent_rate_limit:{$agent->id}";
        $currentMinute = now()->format('Y-m-d H:i');
        $cacheKey = "{$key}:{$currentMinute}";

        Cache::add($cacheKey, 0, 60);
        Cache::increment($cacheKey);
    }

    /**
     * Get remaining rate limit for agent.
     */
    public function getRemainingRateLimit(Agent $agent): int
    {
        $key = "agent_rate_limit:{$agent->id}";
        $currentMinute = now()->format('Y-m-d H:i');
        $cacheKey = "{$key}:{$currentMinute}";

        $attempts = Cache::get($cacheKey, 0);

        return max(0, $agent->rate_limit - $attempts);
    }

    /**
     * Get reset time for rate limit.
     */
    public function getRateLimitResetTime(): int
    {
        $now = now();
        $nextMinute = $now->copy()->addMinute()->startOfMinute();

        return $nextMinute->timestamp;
    }

    /**
     * Clear authentication cache for an agent.
     */
    public function clearAuthCache(Agent $agent): void
    {
        $hashedKey = hash('sha256', $agent->api_key);
        Cache::forget("agent_auth:{$hashedKey}");
    }
}
