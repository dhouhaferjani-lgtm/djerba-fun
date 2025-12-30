<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gateways = [
            [
                'name' => 'mock',
                'slug' => 'mock',
                'display_name' => 'Mock Payment (Testing)',
                'description' => 'Mock payment gateway for testing and development. Always succeeds after 2-second delay.',
                'driver' => 'mock',
                'is_enabled' => true,
                'is_default' => true,
                'priority' => 0,
                'test_mode' => true,
                'configuration' => [],
            ],
            [
                'name' => 'offline_payment',
                'slug' => 'offline-payment',
                'display_name' => 'Offline Payment',
                'description' => 'Accept cash or other offline payment methods. Payment must be confirmed manually.',
                'driver' => 'offline',
                'is_enabled' => true,
                'is_default' => false,
                'priority' => 10,
                'test_mode' => false,
                'configuration' => [
                    'instructions' => 'Please pay the total amount in cash upon arrival or as instructed by your tour operator.',
                ],
            ],
            [
                'name' => 'stripe',
                'slug' => 'stripe',
                'display_name' => 'Stripe',
                'description' => 'Accept credit and debit card payments securely through Stripe.',
                'driver' => 'stripe',
                'is_enabled' => false,
                'is_default' => false,
                'priority' => 20,
                'test_mode' => true,
                'configuration' => [
                    'publishable_key' => '',
                    'secret_key' => '',
                    'webhook_secret' => '',
                ],
            ],
            [
                'name' => 'click_to_pay',
                'slug' => 'click-to-pay',
                'display_name' => 'Click to Pay',
                'description' => 'Fast and secure payments with Visa Click to Pay (Tunisian payment processor).',
                'driver' => 'clicktopay',
                'is_enabled' => false,
                'is_default' => false,
                'priority' => 30,
                'test_mode' => true,
                'configuration' => [
                    'merchant_id' => '',
                    'api_key' => '',
                    'shared_secret' => '',
                ],
            ],
            [
                'name' => 'bank_transfer',
                'slug' => 'bank-transfer',
                'display_name' => 'Bank Transfer',
                'description' => 'Accept direct bank transfers. Payment must be confirmed manually.',
                'driver' => 'bank_transfer',
                'is_enabled' => false,
                'is_default' => false,
                'priority' => 40,
                'test_mode' => false,
                'configuration' => [
                    'bank_name' => 'Example Bank',
                    'account_number' => '',
                    'routing_number' => '',
                    'iban' => '',
                    'swift_code' => '',
                    'instructions' => 'Please transfer the total amount to the bank account details provided. Include your booking number as the reference.',
                ],
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['slug' => $gateway['slug']],
                $gateway
            );
        }
    }
}
