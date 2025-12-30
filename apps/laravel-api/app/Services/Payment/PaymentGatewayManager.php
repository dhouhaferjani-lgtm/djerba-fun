<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @var array<string, PaymentGateway>
     */
    private array $gateways = [];

    /**
     * Register a payment gateway.
     */
    public function register(string $name, PaymentGateway $gateway): void
    {
        $this->gateways[$name] = $gateway;
    }

    /**
     * Get a payment gateway by name.
     * Checks if the gateway is enabled in the database before returning.
     */
    public function gateway(string $name): PaymentGateway
    {
        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not registered.");
        }

        // Check if gateway is enabled in database
        $this->ensureGatewayEnabled($name);

        return $this->gateways[$name];
    }

    /**
     * Get the default payment gateway.
     * Returns the gateway marked as default in the database, or falls back to config.
     */
    public function default(): PaymentGateway
    {
        // Try to get the default gateway from database
        $defaultGatewayModel = \App\Models\PaymentGateway::default()->first();

        if ($defaultGatewayModel) {
            $gatewayDriver = $defaultGatewayModel->driver;

            if ($this->has($gatewayDriver)) {
                return $this->gateway($gatewayDriver);
            }
        }

        // Fallback to config
        $defaultGateway = config('payment.default_gateway', 'mock');

        return $this->gateway($defaultGateway);
    }

    /**
     * Check if a gateway is registered.
     */
    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Get all registered gateways.
     *
     * @return array<string, PaymentGateway>
     */
    public function all(): array
    {
        return $this->gateways;
    }

    /**
     * Get all enabled gateways from the database.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getEnabledGateways()
    {
        return \App\Models\PaymentGateway::enabled()->orderedByPriority()->get();
    }

    /**
     * Check if a gateway is enabled in the database.
     *
     * @throws InvalidArgumentException
     */
    private function ensureGatewayEnabled(string $name): void
    {
        $gatewayModel = \App\Models\PaymentGateway::where('driver', $name)
            ->orWhere('slug', $name)
            ->first();

        if ($gatewayModel && ! $gatewayModel->is_enabled) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is disabled.");
        }
    }

    /**
     * Get available payment methods for the user based on enabled gateways.
     *
     * @return array
     */
    public function getAvailablePaymentMethods(): array
    {
        $enabledGateways = $this->getEnabledGateways();
        $methods = [];

        foreach ($enabledGateways as $gateway) {
            $methods[] = [
                'driver' => $gateway->driver,
                'display_name' => $gateway->display_name,
                'description' => $gateway->description,
                'is_default' => $gateway->is_default,
            ];
        }

        return $methods;
    }
}
