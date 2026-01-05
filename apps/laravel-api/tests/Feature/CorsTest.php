<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * CORS Configuration Tests
 *
 * Purpose: Ensure CORS is configured to allow frontend on all development ports
 * Bug Reference: January 2026 - Frontend started on port 3001 (instead of 3000)
 * due to port conflict, but CORS only allowed 3000, causing API call failures
 */
class CorsTest extends TestCase
{
    /**
     * Test that CORS configuration includes all required development origins
     */
    public function test_cors_allows_all_development_ports(): void
    {
        $corsConfig = config('cors.allowed_origins');

        // CRITICAL: Must allow both localhost and 127.0.0.1 on ports 3000 and 3001
        $requiredOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
        ];

        foreach ($requiredOrigins as $origin) {
            $this->assertContains(
                $origin,
                $corsConfig,
                "CORS config must include {$origin} to handle port conflicts"
            );
        }
    }

    /**
     * Test that CORS allows required paths
     */
    public function test_cors_includes_api_paths(): void
    {
        $corsConfig = config('cors.paths');

        $this->assertContains('api/*', $corsConfig);
        $this->assertContains('sanctum/csrf-cookie', $corsConfig);
    }

    /**
     * Test that CORS supports credentials (required for Sanctum)
     */
    public function test_cors_supports_credentials(): void
    {
        $supportsCredentials = config('cors.supports_credentials');

        $this->assertTrue(
            $supportsCredentials,
            'CORS must support credentials for Sanctum authentication'
        );
    }

    /**
     * Test actual CORS headers in preflight request
     */
    public function test_preflight_request_returns_correct_headers(): void
    {
        $response = $this->options('/api/v1/listings', [
            'Origin' => 'http://localhost:3001',
            'Access-Control-Request-Method' => 'GET',
        ]);

        $response->assertHeader('Access-Control-Allow-Origin', 'http://localhost:3001');
        $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    }
}
