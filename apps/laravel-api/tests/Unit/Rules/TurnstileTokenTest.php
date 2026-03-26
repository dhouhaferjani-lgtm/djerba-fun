<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\TurnstileToken;
use App\Services\TurnstileService;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

/**
 * Test suite for TurnstileToken validation rule.
 *
 * BDD Scenarios:
 * - Validation passes when Turnstile is disabled
 * - Validation fails for empty token when enabled
 * - Validation passes for valid token
 * - Validation fails for invalid token
 */
class TurnstileTokenTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Test validation passes when Turnstile is disabled.
     */
    public function test_validation_passes_when_disabled(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', false);
        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', '', function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertFalse($failCalled, 'Validation should pass when disabled');
    }

    /**
     * Test validation passes when disabled even with empty token.
     */
    public function test_validation_passes_when_disabled_with_empty_token(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', false);
        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', null, function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertFalse($failCalled, 'Validation should pass when disabled even with null token');
    }

    /**
     * Test validation fails for empty token when enabled.
     */
    public function test_validation_fails_for_empty_token_when_enabled(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', true);
        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', '', function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertTrue($failCalled, 'Validation should fail for empty token when enabled');
    }

    /**
     * Test validation fails for null token when enabled.
     */
    public function test_validation_fails_for_null_token_when_enabled(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', true);
        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', null, function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertTrue($failCalled, 'Validation should fail for null token when enabled');
    }

    /**
     * Test validation passes for valid token.
     */
    public function test_validation_passes_for_valid_token(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', true);

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('verify')
            ->with('valid-token', '127.0.0.1')
            ->once()
            ->andReturn([
                'success' => true,
                'error_codes' => [],
                'message' => 'Verified',
            ]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', 'valid-token', function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertFalse($failCalled, 'Validation should pass for valid token');
    }

    /**
     * Test validation fails for invalid token.
     */
    public function test_validation_fails_for_invalid_token(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', true);

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('verify')
            ->with('invalid-token', '127.0.0.1')
            ->once()
            ->andReturn([
                'success' => false,
                'error_codes' => ['invalid-input-response'],
                'message' => 'Turnstile verification failed',
            ]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', 'invalid-token', function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertTrue($failCalled, 'Validation should fail for invalid token');
    }

    /**
     * Test rule works without IP address.
     */
    public function test_validation_works_without_ip_address(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', true);

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('verify')
            ->with('valid-token', null)
            ->once()
            ->andReturn([
                'success' => true,
                'error_codes' => [],
                'message' => 'Verified',
            ]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileToken(); // No IP
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', 'valid-token', function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertFalse($failCalled, 'Validation should pass without IP address');
    }

    /**
     * Test validation handles service failure with fail-open mode.
     */
    public function test_validation_handles_service_failure_with_fail_open(): void
    {
        // Arrange
        Config::set('services.turnstile.enabled', true);

        $mockService = Mockery::mock(TurnstileService::class);
        $mockService->shouldReceive('verify')
            ->with('some-token', '127.0.0.1')
            ->once()
            ->andReturn([
                'success' => true, // Fail-open returns success
                'error_codes' => [],
                'message' => 'Fail-open: Service unavailable',
            ]);

        $this->app->instance(TurnstileService::class, $mockService);

        $rule = new TurnstileToken('127.0.0.1');
        $failCalled = false;

        // Act
        $rule->validate('cf_turnstile_response', 'some-token', function () use (&$failCalled) {
            $failCalled = true;
        });

        // Assert
        $this->assertFalse($failCalled, 'Validation should pass when service fails with fail-open');
    }
}
