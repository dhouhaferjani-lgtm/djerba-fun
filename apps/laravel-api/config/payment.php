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
