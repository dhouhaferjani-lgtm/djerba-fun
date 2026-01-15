<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Supported Locales
    |--------------------------------------------------------------------------
    |
    | Locales supported by this application.
    |
    */
    'supportedLocales' => [
        'en' => ['name' => 'English', 'script' => 'Latn', 'native' => 'English', 'regional' => 'en_GB'],
        'fr' => ['name' => 'French', 'script' => 'Latn', 'native' => 'français', 'regional' => 'fr_FR'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Use Accepted Language Header
    |--------------------------------------------------------------------------
    |
    | If true, this will determine the locale from the Accept-Language header.
    |
    */
    'useAcceptLanguageHeader' => true,

    /*
    |--------------------------------------------------------------------------
    | Hide Default Locale in URL
    |--------------------------------------------------------------------------
    |
    | If true, the default locale will not be shown in the URL.
    |
    */
    'hideDefaultLocaleInURL' => true,

    /*
    |--------------------------------------------------------------------------
    | Locale Separator
    |--------------------------------------------------------------------------
    |
    | This option determines the separator used in the locale string.
    |
    */
    'localeSeparator' => '-',

    /*
    |--------------------------------------------------------------------------
    | URLs Ignoring Locale
    |--------------------------------------------------------------------------
    |
    | These URLs will not be processed by the localization middleware.
    |
    */
    'urlsIgnored' => [
        '/api/*',
        '/sanctum/*',
        '/horizon/*',
        '/admin/*',
        '/vendor/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Localization Routes Prefix
    |--------------------------------------------------------------------------
    |
    | Set a prefix for localized routes.
    |
    */
    'localesMapping' => [],

    /*
    |--------------------------------------------------------------------------
    | UTF-8 suffix
    |--------------------------------------------------------------------------
    |
    | Suffix for the regional locale identifier.
    |
    */
    'utf8suffix' => '.UTF-8',

    /*
    |--------------------------------------------------------------------------
    | Locale HTTP Header Name
    |--------------------------------------------------------------------------
    |
    | HTTP header name to look for locale.
    |
    */
    'httpMethodsIgnored' => ['POST', 'PUT', 'PATCH', 'DELETE'],
];
