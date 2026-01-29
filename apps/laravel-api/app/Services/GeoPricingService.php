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
     * Get the real client IP address from proxy headers.
     *
     * Priority:
     * 1. CF-Connecting-IP (Cloudflare)
     * 2. X-Real-IP (nginx)
     * 3. X-Forwarded-For (first IP in chain)
     * 4. $request->ip() (fallback)
     */
    public function getRealClientIP(Request $request): string
    {
        // Priority 1: Cloudflare header
        $cfIp = $request->header('CF-Connecting-IP');
        if ($cfIp && filter_var($cfIp, FILTER_VALIDATE_IP)) {
            return $cfIp;
        }

        // Priority 2: X-Real-IP (nginx)
        $realIp = $request->header('X-Real-IP');
        if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP)) {
            return $realIp;
        }

        // Priority 3: X-Forwarded-For (first IP in chain is the client)
        $forwardedFor = $request->header('X-Forwarded-For');
        if ($forwardedFor) {
            $ips = array_map('trim', explode(',', $forwardedFor));
            $clientIp = $ips[0] ?? null;
            if ($clientIp && filter_var($clientIp, FILTER_VALIDATE_IP)) {
                return $clientIp;
            }
        }

        // Fallback to Laravel's method
        return $request->ip() ?? '127.0.0.1';
    }

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

        // Priority 2: Check IP geolocation (using real client IP)
        $ip = $this->getRealClientIP($request);

        if ($ip && $ip !== '127.0.0.1' && $ip !== '::1' && ! $this->isPrivateIP($ip)) {
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
        if (! $user) {
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

        // Check IP (using real client IP from proxy headers)
        $ip = $this->getRealClientIP($request);

        if ($ip && $ip !== '127.0.0.1' && $ip !== '::1' && ! $this->isPrivateIP($ip)) {
            $country = $this->getCountryFromIP($ip);

            if ($country) {
                $currency = $country === 'TN' ? 'TND' : 'EUR';
                $source = 'ip_geolocation';

                return compact('currency', 'source', 'country', 'ip');
            }
        }

        return compact('currency', 'source');
    }

    /**
     * Determine pricing country with billing address as final authority.
     *
     * This method implements the PPP pricing hierarchy:
     * 1. Billing address (final authority at checkout)
     * 2. User-selected country (if manual selection enabled)
     * 3. IP geolocation (for browsing)
     *
     * @param  array|null  $billingAddress  Billing address with country_code
     * @param  string|null  $userSelectedCountry  Manually selected country code
     * @param  string|null  $ipAddress  IP address for geolocation
     * @return array{country_code: string, source: string}
     */
    public function determinePricingCountry(
        ?array $billingAddress = null,
        ?string $userSelectedCountry = null,
        ?string $ipAddress = null
    ): array {
        // Priority 1: Billing address (final authority)
        if ($billingAddress && ! empty($billingAddress['country_code'])) {
            return [
                'country_code' => strtoupper($billingAddress['country_code']),
                'source' => 'billing_address',
            ];
        }

        // Priority 2: User-selected country (if manual selection enabled)
        if ($userSelectedCountry) {
            return [
                'country_code' => strtoupper($userSelectedCountry),
                'source' => 'user_selection',
            ];
        }

        // Priority 3: IP geolocation (use provided IP or extract from request)
        $ipAddress = $ipAddress ?? $this->getRealClientIP(request());
        $geoData = $this->detectCountryFromIp($ipAddress);

        return [
            'country_code' => $geoData['countryCode'] ?? 'FR',
            'source' => 'ip_geo',
        ];
    }

    /**
     * Detect country from IP address and return structured geo data.
     *
     * @param  string  $ipAddress  IP address to geolocate
     * @return array{countryCode: string|null, countryName: string|null}
     */
    public function detectCountryFromIp(string $ipAddress): array
    {
        // Skip private/local IPs (including IPv6 localhost)
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1' || $this->isPrivateIP($ipAddress)) {
            return ['countryCode' => null, 'countryName' => null];
        }

        // Use existing getCountryFromIP method
        $countryCode = $this->getCountryFromIP($ipAddress);

        return [
            'countryCode' => $countryCode,
            'countryName' => $countryCode ? $this->getCountryName($countryCode) : null,
        ];
    }

    /**
     * Get currency for country based on PPP pricing rules.
     *
     * Currently: Tunisia (TN) = TND, all others = EUR
     * This can be extended to support more currencies in the future.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @return string Currency code (ISO 4217)
     */
    public function getCurrencyForCountry(string $countryCode): string
    {
        return strtoupper($countryCode) === 'TN' ? 'TND' : 'EUR';
    }

    /**
     * Get human-readable country name from country code.
     *
     * @param  string  $countryCode  ISO 3166-1 alpha-2 country code
     * @return string|null Country name or null if unknown
     */
    protected function getCountryName(string $countryCode): ?string
    {
        // Common country codes - extend as needed
        $countries = [
            'TN' => 'Tunisia',
            'FR' => 'France',
            'DE' => 'Germany',
            'GB' => 'United Kingdom',
            'US' => 'United States',
            'CA' => 'Canada',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'BE' => 'Belgium',
            'CH' => 'Switzerland',
        ];

        return $countries[strtoupper($countryCode)] ?? null;
    }
}
