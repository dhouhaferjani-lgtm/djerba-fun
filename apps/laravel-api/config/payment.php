<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Payment Gateway
    |--------------------------------------------------------------------------
    |
    | This option controls the default payment gateway that will be used
    | when none is explicitly specified.
    |
    */

    'default_gateway' => env('PAYMENT_DEFAULT_GATEWAY', 'mock'),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateways
    |--------------------------------------------------------------------------
    |
    | Here you can configure the various payment gateways available
    | in your application.
    |
    */

    'gateways' => [
        'mock' => [
            'enabled' => env('PAYMENT_MOCK_ENABLED', true),
            'delay_seconds' => env('PAYMENT_MOCK_DELAY', 2),
        ],

        'offline' => [
            'enabled' => env('PAYMENT_OFFLINE_ENABLED', true),
        ],

        'stripe' => [
            'enabled' => env('PAYMENT_STRIPE_ENABLED', false),
            'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
            'secret_key' => env('STRIPE_SECRET_KEY'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'clicktopay' => [
            'enabled' => env('PAYMENT_CLICKTOPAY_ENABLED', false),
            'merchant_id' => env('CLICKTOPAY_MERCHANT_ID'),
            'api_key' => env('CLICKTOPAY_API_KEY'),
            'secret_key' => env('CLICKTOPAY_SECRET_KEY'),
            'test_mode' => env('CLICKTOPAY_TEST_MODE', true),
        ],

        'bank_transfer' => [
            'enabled' => env('PAYMENT_BANK_TRANSFER_ENABLED', false),
            'bank_name' => env('BANK_TRANSFER_BANK_NAME'),
            'account_number' => env('BANK_TRANSFER_ACCOUNT_NUMBER'),
            'iban' => env('BANK_TRANSFER_IBAN'),
            'swift_bic' => env('BANK_TRANSFER_SWIFT_BIC'),
            'account_holder' => env('BANK_TRANSFER_ACCOUNT_HOLDER'),
            'instructions' => env('BANK_TRANSFER_INSTRUCTIONS'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Settings
    |--------------------------------------------------------------------------
    |
    | Configure booking-related settings such as hold duration and
    | cancellation policies.
    |
    */

    'booking' => [
        'hold_duration_minutes' => env('BOOKING_HOLD_DURATION', 15),
        'booking_number_prefix' => env('BOOKING_NUMBER_PREFIX', 'GA'),
    ],
];
