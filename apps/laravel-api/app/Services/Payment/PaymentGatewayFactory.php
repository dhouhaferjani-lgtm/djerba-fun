<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\PlatformSettings;
use Illuminate\Support\Facades\Cache;

/**
 * Payment Gateway Factory
 *
 * Creates and manages payment gateway instances based on platform settings.
 * Handles gateway caching and validation.
 */
class PaymentGatewayFactory
{
    /**
     * Cache TTL for gateway instances (in seconds).
     */
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Available gateway classes.
     */
    private const GATEWAY_CLASSES = [
        'mock' => MockPaymentGateway::class,
        'stripe' => StripePaymentGateway::class,
        'clicktopay' => ClickToPayGateway::class,
        'offline' => OfflinePaymentGateway::class,
        'bank_transfer' => OfflinePaymentGateway::class, // Bank transfer uses offline gateway
    ];

    /**
     * Cached gateway instances.
     */
    private array $instances = [];

    /**
     * Get the default payment gateway.
     *
     * @return PaymentGateway
     *
     * @throws \Exception If default gateway is not configured or enabled
     */
    public function getDefaultGateway(): PaymentGateway
    {
        $settings = PlatformSettings::instance();
        $gatewayName = $settings->default_payment_gateway ?? config('payment.default_gateway', 'mock');

        return $this->make($gatewayName);
    }

    /**
     * Create or retrieve a payment gateway instance.
     *
     * @param  string  $gateway  Gateway name (mock, stripe, clicktopay, offline, bank_transfer)
     * @return PaymentGateway
     *
     * @throws \Exception If gateway is not supported, not enabled, or not properly configured
     */
    public function make(string $gateway): PaymentGateway
    {
        // Return cached instance if available
        if (isset($this->instances[$gateway])) {
            return $this->instances[$gateway];
        }

        // Validate gateway name
        if (! isset(self::GATEWAY_CLASSES[$gateway])) {
            throw new \Exception("Payment gateway '{$gateway}' is not supported. Available gateways: " . implode(', ', array_keys(self::GATEWAY_CLASSES)));
        }

        // Check if gateway is enabled
        if (! $this->isGatewayEnabled($gateway)) {
            throw new \Exception("Payment gateway '{$gateway}' is not enabled. Please enable it in platform settings.");
        }

        // Validate gateway configuration
        $this->validateGatewayConfiguration($gateway);

        // Instantiate gateway
        $gatewayClass = self::GATEWAY_CLASSES[$gateway];

        if (! class_exists($gatewayClass)) {
            throw new \Exception("Payment gateway class '{$gatewayClass}' not found.");
        }

        $instance = new $gatewayClass();

        if (! $instance instanceof PaymentGateway) {
            throw new \Exception("Gateway class '{$gatewayClass}' must implement PaymentGateway interface.");
        }

        // Cache the instance
        $this->instances[$gateway] = $instance;

        return $instance;
    }

    /**
     * Check if a gateway is enabled.
     *
     * @param  string  $gateway  Gateway name
     * @return bool
     */
    public function isGatewayEnabled(string $gateway): bool
    {
        $config = config("payment.gateways.{$gateway}");

        // If no config exists, assume disabled
        if (! $config) {
            return false;
        }

        // Check enabled flag in config
        return $config['enabled'] ?? false;
    }

    /**
     * Get all enabled gateways.
     *
     * @return array Array of enabled gateway names
     */
    public function getEnabledGateways(): array
    {
        $enabled = [];

        foreach (array_keys(self::GATEWAY_CLASSES) as $gateway) {
            if ($this->isGatewayEnabled($gateway)) {
                $enabled[] = $gateway;
            }
        }

        return $enabled;
    }

    /**
     * Get available gateway options for dropdowns/selects.
     *
     * @param  bool  $onlyEnabled  Only return enabled gateways
     * @return array
     */
    public function getGatewayOptions(bool $onlyEnabled = true): array
    {
        $options = [
            'mock' => 'Mock (Development)',
            'stripe' => 'Stripe',
            'clicktopay' => 'Click to Pay (Tunisia)',
            'bank_transfer' => 'Bank Transfer',
            'offline' => 'Offline/Manual',
        ];

        if ($onlyEnabled) {
            $enabled = $this->getEnabledGateways();

            return array_intersect_key($options, array_flip($enabled));
        }

        return $options;
    }

    /**
     * Validate gateway configuration.
     *
     * @param  string  $gateway  Gateway name
     *
     * @throws \Exception If configuration is invalid
     */
    private function validateGatewayConfiguration(string $gateway): void
    {
        $config = config("payment.gateways.{$gateway}");

        switch ($gateway) {
            case 'stripe':
                if (empty($config['secret_key']) || empty($config['publishable_key'])) {
                    throw new \Exception('Stripe gateway requires secret_key and publishable_key to be configured.');
                }
                break;

            case 'clicktopay':
                if (empty($config['merchant_id']) || empty($config['api_key'])) {
                    throw new \Exception('Click to Pay gateway requires merchant_id and api_key to be configured.');
                }
                break;

            case 'mock':
                // Mock gateway has no required configuration
                break;

            case 'offline':
            case 'bank_transfer':
                // Offline/bank transfer have no required configuration
                break;

            default:
                // No specific validation for other gateways
                break;
        }
    }

    /**
     * Clear cached gateway instances.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->instances = [];
    }

    /**
     * Check if a gateway supports refunds.
     *
     * @param  string  $gateway  Gateway name
     * @return bool
     */
    public function supportsRefunds(string $gateway): bool
    {
        // All gateways implement refund in the interface
        // But some may have limitations
        return match ($gateway) {
            'mock', 'stripe', 'clicktopay' => true,
            'offline', 'bank_transfer' => false, // Manual refunds only
            default => false,
        };
    }

    /**
     * Check if a gateway supports partial refunds.
     *
     * @param  string  $gateway  Gateway name
     * @return bool
     */
    public function supportsPartialRefunds(string $gateway): bool
    {
        return match ($gateway) {
            'mock', 'stripe', 'clicktopay' => true,
            default => false,
        };
    }

    /**
     * Get gateway display name.
     *
     * @param  string  $gateway  Gateway name
     * @return string
     */
    public function getGatewayDisplayName(string $gateway): string
    {
        $options = $this->getGatewayOptions(false);

        return $options[$gateway] ?? ucfirst($gateway);
    }
}
