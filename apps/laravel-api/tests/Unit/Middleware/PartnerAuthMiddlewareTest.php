<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Http\Middleware\PartnerAuthMiddleware;
use App\Models\Partner;
use App\Models\PartnerApiKey;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PartnerAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected PartnerAuthMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new PartnerAuthMiddleware();
    }

    /**
     * Test partner authentication with valid credentials.
     */
    public function test_partner_authentication_with_valid_credentials(): void
    {
        // Arrange
        $partner = Partner::factory()->create(['is_active' => true]);
        $apiKey = PartnerApiKey::factory()->create([
            'partner_id' => $partner->id,
            'is_active' => true,
        ]);

        $request = Request::create('/api/partner/test', 'GET');
        $request->headers->set('X-Partner-Key', $apiKey->key);
        $request->headers->set('X-Partner-Secret', $apiKey->secret);

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * Test partner authentication fails without headers.
     */
    public function test_authentication_fails_without_headers(): void
    {
        // Arrange
        $request = Request::create('/api/partner/test', 'GET');

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test authentication fails with invalid API key.
     */
    public function test_authentication_fails_with_invalid_key(): void
    {
        // Arrange
        $request = Request::create('/api/partner/test', 'GET');
        $request->headers->set('X-Partner-Key', 'invalid-key');
        $request->headers->set('X-Partner-Secret', 'invalid-secret');

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test authentication fails with inactive partner.
     */
    public function test_authentication_fails_with_inactive_partner(): void
    {
        // Arrange
        $partner = Partner::factory()->inactive()->create();
        $apiKey = PartnerApiKey::factory()->create([
            'partner_id' => $partner->id,
            'is_active' => true,
        ]);

        $request = Request::create('/api/partner/test', 'GET');
        $request->headers->set('X-Partner-Key', $apiKey->key);
        $request->headers->set('X-Partner-Secret', $apiKey->secret);

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertEquals(403, $response->getStatusCode());
    }

    /**
     * Test authentication fails with inactive API key.
     */
    public function test_authentication_fails_with_inactive_api_key(): void
    {
        // Arrange
        $partner = Partner::factory()->create(['is_active' => true]);
        $apiKey = PartnerApiKey::factory()->create([
            'partner_id' => $partner->id,
            'is_active' => false,
        ]);

        $request = Request::create('/api/partner/test', 'GET');
        $request->headers->set('X-Partner-Key', $apiKey->key);
        $request->headers->set('X-Partner-Secret', $apiKey->secret);

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
    }

    /**
     * Test authentication fails with expired API key.
     */
    public function test_authentication_fails_with_expired_api_key(): void
    {
        // Arrange
        $partner = Partner::factory()->create(['is_active' => true]);
        $apiKey = PartnerApiKey::factory()->create([
            'partner_id' => $partner->id,
            'is_active' => true,
            'expires_at' => now()->subDay(),
        ]);

        $request = Request::create('/api/partner/test', 'GET');
        $request->headers->set('X-Partner-Key', $apiKey->key);
        $request->headers->set('X-Partner-Secret', $apiKey->secret);

        // Act
        $response = $this->middleware->handle($request, function ($req) {
            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('expired', $response->getContent());
    }

    /**
     * Test successful authentication sets partner in request.
     */
    public function test_successful_authentication_sets_partner_in_request(): void
    {
        // Arrange
        $partner = Partner::factory()->create(['is_active' => true]);
        $apiKey = PartnerApiKey::factory()->create([
            'partner_id' => $partner->id,
            'is_active' => true,
        ]);

        $request = Request::create('/api/partner/test', 'GET');
        $request->headers->set('X-Partner-Key', $apiKey->key);
        $request->headers->set('X-Partner-Secret', $apiKey->secret);

        $partnerFromRequest = null;

        // Act
        $this->middleware->handle($request, function ($req) use (&$partnerFromRequest) {
            $partnerFromRequest = $req->partner;

            return response()->json(['success' => true]);
        });

        // Assert
        $this->assertNotNull($partnerFromRequest);
        $this->assertEquals($partner->id, $partnerFromRequest->id);
    }
}
