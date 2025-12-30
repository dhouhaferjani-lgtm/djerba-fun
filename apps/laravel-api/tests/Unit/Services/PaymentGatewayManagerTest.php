<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Contracts\PaymentGateway;
use App\Models\PaymentGateway as PaymentGatewayModel;
use App\Services\Payment\MockPaymentGateway;
use App\Services\Payment\PaymentGatewayManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class PaymentGatewayManagerTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentGatewayManager $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = new PaymentGatewayManager();
    }

    /**
     * Test registering a payment gateway.
     */
    public function test_register_payment_gateway(): void
    {
        // Arrange
        $mockGateway = new MockPaymentGateway();

        // Act
        $this->manager->register('mock', $mockGateway);

        // Assert
        $this->assertTrue($this->manager->has('mock'));
    }

    /**
     * Test getting a registered gateway.
     */
    public function test_get_registered_gateway(): void
    {
        // Arrange
        $mockGateway = new MockPaymentGateway();
        $this->manager->register('mock', $mockGateway);

        // Create enabled gateway in database
        PaymentGatewayModel::create([
            'name' => 'Mock Gateway',
            'driver' => 'mock',
            'slug' => 'mock',
            'is_enabled' => true,
            'is_default' => false,
            'priority' => 1,
        ]);

        // Act
        $gateway = $this->manager->gateway('mock');

        // Assert
        $this->assertInstanceOf(PaymentGateway::class, $gateway);
        $this->assertInstanceOf(MockPaymentGateway::class, $gateway);
    }

    /**
     * Test getting an unregistered gateway throws exception.
     */
    public function test_get_unregistered_gateway_throws_exception(): void
    {
        // Assert
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment gateway [nonexistent] is not registered.');

        // Act
        $this->manager->gateway('nonexistent');
    }

    /**
     * Test getting default gateway.
     */
    public function test_get_default_gateway(): void
    {
        // Arrange
        $mockGateway = new MockPaymentGateway();
        $this->manager->register('mock', $mockGateway);

        PaymentGatewayModel::create([
            'name' => 'Mock Gateway',
            'driver' => 'mock',
            'slug' => 'mock',
            'is_enabled' => true,
            'is_default' => true,
            'priority' => 1,
        ]);

        // Act
        $gateway = $this->manager->default();

        // Assert
        $this->assertInstanceOf(MockPaymentGateway::class, $gateway);
    }

    /**
     * Test has method correctly identifies registered gateways.
     */
    public function test_has_method_works_correctly(): void
    {
        // Arrange
        $mockGateway = new MockPaymentGateway();
        $this->manager->register('mock', $mockGateway);

        // Assert
        $this->assertTrue($this->manager->has('mock'));
        $this->assertFalse($this->manager->has('stripe'));
    }

    /**
     * Test getting all registered gateways.
     */
    public function test_get_all_registered_gateways(): void
    {
        // Arrange
        $mockGateway = new MockPaymentGateway();
        $this->manager->register('mock', $mockGateway);
        $this->manager->register('test', $mockGateway);

        // Act
        $all = $this->manager->all();

        // Assert
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('mock', $all);
        $this->assertArrayHasKey('test', $all);
    }

    /**
     * Test getting enabled gateways from database.
     */
    public function test_get_enabled_gateways_from_database(): void
    {
        // Arrange
        PaymentGatewayModel::create([
            'name' => 'Mock Gateway',
            'driver' => 'mock',
            'slug' => 'mock',
            'is_enabled' => true,
            'is_default' => false,
            'priority' => 1,
        ]);

        PaymentGatewayModel::create([
            'name' => 'Disabled Gateway',
            'driver' => 'disabled',
            'slug' => 'disabled',
            'is_enabled' => false,
            'is_default' => false,
            'priority' => 2,
        ]);

        // Act
        $enabled = $this->manager->getEnabledGateways();

        // Assert
        $this->assertCount(1, $enabled);
        $this->assertEquals('mock', $enabled->first()->driver);
    }

    /**
     * Test accessing disabled gateway throws exception.
     */
    public function test_accessing_disabled_gateway_throws_exception(): void
    {
        // Arrange
        $mockGateway = new MockPaymentGateway();
        $this->manager->register('disabled', $mockGateway);

        PaymentGatewayModel::create([
            'name' => 'Disabled Gateway',
            'driver' => 'disabled',
            'slug' => 'disabled',
            'is_enabled' => false,
            'is_default' => false,
            'priority' => 1,
        ]);

        // Assert
        $this->expectException(InvalidArgumentException::class);

        // Act
        $this->manager->gateway('disabled');
    }
}
