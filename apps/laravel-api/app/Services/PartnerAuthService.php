<?php

namespace App\Services;

use App\Models\Partner;
use Illuminate\Support\Facades\Cache;

class PartnerAuthService
{
    /**
     * Authenticate a partner using API key and secret.
     */
    public function authenticate(string $apiKey, string $apiSecret): ?Partner
    {
        // Hash the API key for lookup
        $hashedKey = hash('sha256', $apiKey);

        // Try to get from cache first
        $cacheKey = "partner_auth:{$hashedKey}";

        $partner = Cache::remember($cacheKey, 300, function () use ($hashedKey) {
            return Partner::active()
                ->where('api_key', $hashedKey)
                ->first();
        });

        if (! $partner) {
            return null;
        }

        // Verify the secret
        if (! $partner->verifySecret($apiSecret)) {
            return null;
        }

        return $partner;
    }

    /**
     * Check if a partner has permission for an action.
     */
    public function checkPermission(Partner $partner, string $permission): bool
    {
        return $partner->hasPermission($permission);
    }

    /**
     * Get rate limit for a partner.
     */
    public function getRateLimit(Partner $partner): int
    {
        return $partner->rate_limit;
    }

    /**
     * Check if partner has exceeded rate limit.
     */
    public function hasExceededRateLimit(Partner $partner): bool
    {
        $key = "partner_rate_limit:{$partner->id}";
        $attempts = Cache::get($key, 0);

        return $attempts >= $partner->rate_limit;
    }

    /**
     * Increment rate limit counter.
     */
    public function incrementRateLimit(Partner $partner): void
    {
        $key = "partner_rate_limit:{$partner->id}";
        $currentMinute = now()->format('Y-m-d H:i');
        $cacheKey = "{$key}:{$currentMinute}";

        Cache::add($cacheKey, 0, 60);
        Cache::increment($cacheKey);
    }

    /**
     * Get remaining rate limit for partner.
     */
    public function getRemainingRateLimit(Partner $partner): int
    {
        $key = "partner_rate_limit:{$partner->id}";
        $currentMinute = now()->format('Y-m-d H:i');
        $cacheKey = "{$key}:{$currentMinute}";

        $attempts = Cache::get($cacheKey, 0);

        return max(0, $partner->rate_limit - $attempts);
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
     * Clear authentication cache for a partner.
     */
    public function clearAuthCache(Partner $partner): void
    {
        $hashedKey = hash('sha256', $partner->api_key);
        Cache::forget("partner_auth:{$hashedKey}");
    }
}
