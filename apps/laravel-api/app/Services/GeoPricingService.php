<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoPricingService
{
    /**
     * Detect the appropriate currency for the user.
     *
     * Priority:
     * 1. User's billing address country (if logged in)
     * 2. IP geolocation
     * 3. Default to EUR
     */
    public function detectUserCurrency(Request $request, ?User $user = null): string
    {
        // Priority 1: Check user's billing country
        if ($user) {
            $billingCountry = $this->getUserBillingCountry($user);
            if ($billingCountry === 'TN') {
                return 'TND';
            }
            if ($billingCountry !== null) {
                return 'EUR'; // Any other country gets EUR
            }
        }

        // Priority 2: Check IP geolocation
        $ip = $request->ip();
        if ($ip && $ip !== '127.0.0.1' && !$this->isPrivateIP($ip)) {
            $country = $this->getCountryFromIP($ip);
            if ($country === 'TN') {
                return 'TND';
            }
            if ($country !== null) {
                return 'EUR';
            }
        }

        // Priority 3: Default to EUR (international)
        return 'EUR';
    }

    /**
     * Check if the user is from Tunisia based on all available signals.
     */
    public function isTunisianUser(Request $request, ?User $user = null): bool
    {
        return $this->detectUserCurrency($request, $user) === 'TND';
    }

    /**
     * Get country code from IP address using geolocation API.
     */
    public function getCountryFromIP(string $ip): ?string
    {
        // Cache the result for 24 hours
        $cacheKey = 'geo:ip:' . md5($ip);

        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                // Using ip-api.com free tier (limited to 45 requests/minute)
                $response = Http::timeout(3)
                    ->get("http://ip-api.com/json/{$ip}", [
                        'fields' => 'status,countryCode',
                    ]);

                if ($response->successful()) {
                    $data = $response->json();
                    if ($data['status'] === 'success') {
                        return $data['countryCode'] ?? null;
                    }
                }
            } catch (\Exception $e) {
                Log::warning('IP geolocation failed', [
                    'ip' => $ip,
                    'error' => $e->getMessage(),
                ]);
            }

            return null;
        });
    }

    /**
     * Get user's billing country from their profile.
     */
    public function getUserBillingCountry(?User $user): ?string
    {
        if (!$user) {
            return null;
        }

        // Check traveler profile for billing country
        if ($user->travelerProfile) {
            $preferences = $user->travelerProfile->preferences ?? [];
            if (isset($preferences['billing_country'])) {
                return $preferences['billing_country'];
            }
        }

        // Check vendor profile for business country
        if ($user->vendorProfile) {
            // Vendors are typically local (Tunisia)
            return 'TN';
        }

        return null;
    }

    /**
     * Check if IP is private/local.
     */
    protected function isPrivateIP(string $ip): bool
    {
        $private_ranges = [
            '10.0.0.0' => '10.255.255.255',
            '172.16.0.0' => '172.31.255.255',
            '192.168.0.0' => '192.168.255.255',
            '127.0.0.0' => '127.255.255.255',
        ];

        $ip_long = ip2long($ip);

        foreach ($private_ranges as $start => $end) {
            if ($ip_long >= ip2long($start) && $ip_long <= ip2long($end)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get currency with detection metadata (useful for debugging).
     */
    public function detectCurrencyWithMetadata(Request $request, ?User $user = null): array
    {
        $currency = 'EUR';
        $source = 'default';

        // Check billing country
        if ($user) {
            $billingCountry = $this->getUserBillingCountry($user);
            if ($billingCountry) {
                $currency = $billingCountry === 'TN' ? 'TND' : 'EUR';
                $source = 'user_billing';
                return compact('currency', 'source');
            }
        }

        // Check IP
        $ip = $request->ip();
        if ($ip && $ip !== '127.0.0.1' && !$this->isPrivateIP($ip)) {
            $country = $this->getCountryFromIP($ip);
            if ($country) {
                $currency = $country === 'TN' ? 'TND' : 'EUR';
                $source = 'ip_geolocation';
                return compact('currency', 'source', 'country', 'ip');
            }
        }

        return compact('currency', 'source');
    }
}
