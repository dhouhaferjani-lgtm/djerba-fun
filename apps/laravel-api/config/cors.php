<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        // Local development
        'http://localhost:3000',
        'http://localhost:3001',
        // 3100 covers the +100 port shift used when this repo runs alongside
        // the legacy goadventurenew compose project on the same machine.
        'http://localhost:3100',
        'http://localhost:3101',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:3001',
        'http://127.0.0.1:3100',
        'http://127.0.0.1:3101',
    ],

    'allowed_origins_patterns' => [
        // All djerbafun.com subdomains (live, staging, dev)
        '#^https://(.+\.)?djerbafun\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
