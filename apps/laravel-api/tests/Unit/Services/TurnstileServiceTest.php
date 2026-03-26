<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\TurnstileService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Test suite for TurnstileService.
 *
 * BDD Scenarios:
 * - Service returns success when disabled
 * - Service returns error for empty token when enabled
 * - Service returns success for valid token
 * - Service returns error for invalid token
 * - Service handles Cloudflare timeout with fail-open true
 * - Service handles Cloudflare timeout with fail-open false
 * - isEnabled() returns correct value
 */
class TurnstileServiceTest extends TestCase
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /**
     * Test verify returns success when Turnstile is disabled.
     */
    public function test_verify_returns_success_when_disabled(): void
    {
        // Arrange
        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: false,
        );

        // Act
        $result = $service->verify('any-token', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Turnstile disabled', $result['message']);
        $this->assertEmpty($result['error_codes']);
    }

    /**
     * Test verify returns error for empty token when enabled.
     */
    public function test_verify_returns_error_for_empty_token_when_enabled(): void
    {
        // Arrange
        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act
        $result = $service->verify('', '127.0.0.1');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Turnstile token is required', $result['message']);
        $this->assertContains('missing-input-response', $result['error_codes']);
    }

    /**
     * Test verify returns success for valid token.
     */
    public function test_verify_returns_success_for_valid_token(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
            ], 200),
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act
        $result = $service->verify('valid-token', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('Verified', $result['message']);
        $this->assertEmpty($result['error_codes']);

        Http::assertSent(function ($request) {
            return $request->data()['secret'] === 'test-secret'
                && $request->data()['response'] === 'valid-token'
                && $request->data()['remoteip'] === '127.0.0.1';
        });
    }

    /**
     * Test verify returns error for invalid token.
     */
    public function test_verify_returns_error_for_invalid_token(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act
        $result = $service->verify('invalid-token', '127.0.0.1');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertEquals('Turnstile verification failed', $result['message']);
        $this->assertContains('invalid-input-response', $result['error_codes']);
    }

    /**
     * Test verify handles Cloudflare timeout with fail-open true.
     */
    public function test_verify_handles_cloudflare_timeout_with_fail_open_true(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => Http::response(null, 500),
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: true,
            enabled: true,
        );

        // Act
        $result = $service->verify('some-token', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Fail-open', $result['message']);
        $this->assertEmpty($result['error_codes']);
    }

    /**
     * Test verify handles Cloudflare timeout with fail-open false.
     */
    public function test_verify_handles_cloudflare_timeout_with_fail_open_false(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => Http::response(null, 500),
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act
        $result = $service->verify('some-token', '127.0.0.1');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('service-unavailable', $result['error_codes']);
    }

    /**
     * Test verify handles connection exception with fail-open true.
     */
    public function test_verify_handles_exception_with_fail_open_true(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: true,
            enabled: true,
        );

        // Act
        $result = $service->verify('some-token', '127.0.0.1');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('Fail-open', $result['message']);
    }

    /**
     * Test verify handles connection exception with fail-open false.
     */
    public function test_verify_handles_exception_with_fail_open_false(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => function () {
                throw new \Exception('Connection timeout');
            },
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act
        $result = $service->verify('some-token', '127.0.0.1');

        // Assert
        $this->assertFalse($result['success']);
        $this->assertContains('service-unavailable', $result['error_codes']);
    }

    /**
     * Test isEnabled returns correct value when enabled.
     */
    public function test_is_enabled_returns_true_when_enabled(): void
    {
        // Arrange
        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act & Assert
        $this->assertTrue($service->isEnabled());
    }

    /**
     * Test isEnabled returns correct value when disabled.
     */
    public function test_is_enabled_returns_false_when_disabled(): void
    {
        // Arrange
        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: false,
        );

        // Act & Assert
        $this->assertFalse($service->isEnabled());
    }

    /**
     * Test verify passes without remoteIp parameter.
     */
    public function test_verify_works_without_remote_ip(): void
    {
        // Arrange
        Http::fake([
            self::VERIFY_URL => Http::response([
                'success' => true,
            ], 200),
        ]);

        $service = new TurnstileService(
            secretKey: 'test-secret',
            verifyUrl: self::VERIFY_URL,
            timeout: 5,
            failOpen: false,
            enabled: true,
        );

        // Act
        $result = $service->verify('valid-token');

        // Assert
        $this->assertTrue($result['success']);

        Http::assertSent(function ($request) {
            return $request->data()['remoteip'] === null;
        });
    }
}
