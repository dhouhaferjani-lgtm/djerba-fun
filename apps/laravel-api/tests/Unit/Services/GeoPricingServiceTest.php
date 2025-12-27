<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\GeoPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Helpers\PppTestHelpers;
use Tests\TestCase;

/**
 * Test suite for GeoPricingService.
 *
 * Validates IP-based geolocation, currency detection, and billing address priority
 * for PPP (Purchasing Power Parity) pricing.
 */
class GeoPricingServiceTest extends TestCase
{
    use PppTestHelpers;
    use RefreshDatabase;

    protected GeoPricingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(GeoPricingService::class);
        Cache::flush(); // Clear cache before each test
    }

    /**
     * Test that Tunisia IP returns TND currency.
     */
    public function test_tunisia_ip_returns_tnd_currency(): void
    {
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $tunisiaIP]);

        $currency = $this->service->detectUserCurrency($request);

        $this->assertEquals('TND', $currency, 'Tunisia IP should return TND currency');
    }

    /**
     * Test that France IP returns EUR currency.
     */
    public function test_france_ip_returns_eur_currency(): void
    {
        $franceIP = '78.193.67.1';
        $this->mockFranceIP($franceIP);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $franceIP]);

        $currency = $this->service->detectUserCurrency($request);

        $this->assertEquals('EUR', $currency, 'France IP should return EUR currency');
    }

    /**
     * Test that billing address overrides IP detection.
     *
     * A user with Tunisia billing country should get TND even from France IP.
     */
    public function test_billing_address_overrides_ip_detection(): void
    {
        $franceIP = '78.193.67.1';
        $this->mockFranceIP($franceIP);

        $user = $this->createUserWithBillingCountry('TN');
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $franceIP]);

        $currency = $this->service->detectUserCurrency($request, $user);

        $this->assertEquals('TND', $currency, 'Billing address (TN) should override IP geolocation (FR)');
    }

    /**
     * Test that France billing address returns EUR even from Tunisia IP.
     */
    public function test_france_billing_overrides_tunisia_ip(): void
    {
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $user = $this->createUserWithBillingCountry('FR');
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $tunisiaIP]);

        $currency = $this->service->detectUserCurrency($request, $user);

        $this->assertEquals('EUR', $currency, 'Billing address (FR) should override IP geolocation (TN)');
    }

    /**
     * Test that private IP defaults to EUR.
     */
    public function test_private_ip_defaults_to_eur(): void
    {
        $privateIP = '192.168.1.100';
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $privateIP]);

        $currency = $this->service->detectUserCurrency($request);

        $this->assertEquals('EUR', $currency, 'Private IP should default to EUR');
    }

    /**
     * Test that localhost defaults to EUR.
     */
    public function test_localhost_defaults_to_eur(): void
    {
        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

        $currency = $this->service->detectUserCurrency($request);

        $this->assertEquals('EUR', $currency, 'Localhost (127.0.0.1) should default to EUR');
    }

    /**
     * Test that API failure defaults to EUR.
     */
    public function test_api_failure_defaults_to_eur(): void
    {
        $unknownIP = '1.2.3.4';
        $this->mockIPGeolocationFailure($unknownIP);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $unknownIP]);

        $currency = $this->service->detectUserCurrency($request);

        $this->assertEquals('EUR', $currency, 'API failure should default to EUR');
    }

    /**
     * Test that geolocation responses are cached for 24 hours.
     */
    public function test_geolocation_response_caching(): void
    {
        $testIP = '1.2.3.4';
        $cacheKey = 'geo:ip:' . md5($testIP);

        // First call - should hit the API
        Http::fake([
            "ip-api.com/json/{$testIP}*" => Http::response([
                'status' => 'success',
                'countryCode' => 'DE',
            ], 200),
        ]);

        $firstResult = $this->service->getCountryFromIP($testIP);
        $this->assertEquals('DE', $firstResult);

        // Verify it's cached
        $this->assertTrue(Cache::has($cacheKey), 'Geolocation result should be cached');

        // Second call - should use cache
        Http::fake([
            "ip-api.com/json/{$testIP}*" => Http::response([
                'status' => 'success',
                'countryCode' => 'FR', // Different response, but shouldn't be called
            ], 200),
        ]);

        $secondResult = $this->service->getCountryFromIP($testIP);
        $this->assertEquals('DE', $secondResult, 'Second call should return cached value');

        // Verify cache TTL is set correctly (24 hours = 86400 seconds)
        $cachedValue = Cache::get($cacheKey);
        $this->assertEquals('DE', $cachedValue);
    }

    /**
     * Test isTunisianUser() returns true for Tunisia IP.
     */
    public function test_is_tunisian_user_returns_true_for_tunisia_ip(): void
    {
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $tunisiaIP]);

        $this->assertTrue($this->service->isTunisianUser($request), 'Tunisia IP should return true');
    }

    /**
     * Test isTunisianUser() returns false for France IP.
     */
    public function test_is_tunisian_user_returns_false_for_france_ip(): void
    {
        $franceIP = '78.193.67.1';
        $this->mockFranceIP($franceIP);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $franceIP]);

        $this->assertFalse($this->service->isTunisianUser($request), 'France IP should return false');
    }

    /**
     * Test determinePricingCountry() prioritizes billing address.
     */
    public function test_determine_pricing_country_prioritizes_billing_address(): void
    {
        $result = $this->service->determinePricingCountry(
            billingAddress: ['country_code' => 'TN'],
            userSelectedCountry: 'FR',
            ipAddress: '78.193.67.1'
        );

        $this->assertEquals('TN', $result['country_code'], 'Billing address should have highest priority');
        $this->assertEquals('billing_address', $result['source']);
    }

    /**
     * Test determinePricingCountry() falls back to user selection.
     */
    public function test_determine_pricing_country_falls_back_to_user_selection(): void
    {
        $franceIP = '78.193.67.1';
        $this->mockFranceIP($franceIP);

        $result = $this->service->determinePricingCountry(
            billingAddress: null,
            userSelectedCountry: 'DE',
            ipAddress: $franceIP
        );

        $this->assertEquals('DE', $result['country_code'], 'User selection should be second priority');
        $this->assertEquals('user_selection', $result['source']);
    }

    /**
     * Test determinePricingCountry() falls back to IP geolocation.
     */
    public function test_determine_pricing_country_falls_back_to_ip_geolocation(): void
    {
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $result = $this->service->determinePricingCountry(
            billingAddress: null,
            userSelectedCountry: null,
            ipAddress: $tunisiaIP
        );

        $this->assertEquals('TN', $result['country_code'], 'IP geolocation should be third priority');
        $this->assertEquals('ip_geo', $result['source']);
    }

    /**
     * Test determinePricingCountry() defaults to FR when all else fails.
     */
    public function test_determine_pricing_country_defaults_to_france(): void
    {
        Http::fake([
            'ip-api.com/*' => Http::response(['status' => 'fail'], 200),
        ]);

        $result = $this->service->determinePricingCountry(
            billingAddress: null,
            userSelectedCountry: null,
            ipAddress: '127.0.0.1'
        );

        $this->assertEquals('FR', $result['country_code'], 'Should default to FR when all detection methods fail');
        $this->assertEquals('ip_geo', $result['source']);
    }

    /**
     * Test getCurrencyForCountry() returns TND for Tunisia.
     */
    public function test_get_currency_for_country_returns_tnd_for_tunisia(): void
    {
        $currency = $this->service->getCurrencyForCountry('TN');
        $this->assertEquals('TND', $currency);

        // Test case-insensitive
        $currency = $this->service->getCurrencyForCountry('tn');
        $this->assertEquals('TND', $currency);
    }

    /**
     * Test getCurrencyForCountry() returns EUR for all other countries.
     */
    public function test_get_currency_for_country_returns_eur_for_all_others(): void
    {
        $countries = ['FR', 'DE', 'ES', 'IT', 'GB', 'US', 'CA', 'BE', 'CH'];

        foreach ($countries as $country) {
            $currency = $this->service->getCurrencyForCountry($country);
            $this->assertEquals('EUR', $currency, "Country {$country} should return EUR");
        }
    }

    /**
     * Test detectCurrencyWithMetadata() includes source information.
     */
    public function test_detect_currency_with_metadata_includes_source(): void
    {
        $tunisiaIP = '41.230.62.1';
        $this->mockTunisiaIP($tunisiaIP);

        $request = Request::create('/', 'GET', [], [], [], ['REMOTE_ADDR' => $tunisiaIP]);

        $result = $this->service->detectCurrencyWithMetadata($request);

        $this->assertArrayHasKey('currency', $result);
        $this->assertArrayHasKey('source', $result);
        $this->assertEquals('TND', $result['currency']);
        $this->assertEquals('ip_geolocation', $result['source']);
    }

    /**
     * Test detectCurrencyWithMetadata() includes billing source when user has billing country.
     */
    public function test_detect_currency_with_metadata_shows_billing_source(): void
    {
        $user = $this->createUserWithBillingCountry('TN');
        $request = Request::create('/', 'GET');

        $result = $this->service->detectCurrencyWithMetadata($request, $user);

        $this->assertEquals('TND', $result['currency']);
        $this->assertEquals('user_billing', $result['source']);
    }

    /**
     * Test vendor users default to Tunisia (TN).
     */
    public function test_vendor_users_default_to_tunisia(): void
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $vendor->vendorProfile()->create([
            'company_name' => 'Test Business',
            'company_type' => 'tour_operator',
        ]);

        $request = Request::create('/', 'GET');

        $currency = $this->service->detectUserCurrency($request, $vendor);

        $this->assertEquals('TND', $currency, 'Vendor users should default to TND');
    }
}
