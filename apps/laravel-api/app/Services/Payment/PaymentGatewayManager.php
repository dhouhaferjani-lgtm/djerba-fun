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
     */
    public function gateway(string $name): PaymentGateway
    {
        if (! isset($this->gateways[$name])) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not registered.");
        }

        return $this->gateways[$name];
    }

    /**
     * Get the default payment gateway.
     */
    public function default(): PaymentGateway
    {
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
}
