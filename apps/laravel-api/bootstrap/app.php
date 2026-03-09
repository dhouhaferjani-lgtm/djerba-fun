<?php

use App\Models\Listing;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // Explicit model binding for listing by uuid or slug
            Route::bind('listing', function (string $value) {
                // Check if value is a valid UUID format to avoid PostgreSQL type casting errors
                if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
                    return Listing::where('uuid', $value)->firstOrFail();
                }
                // Otherwise try by slug
                return Listing::where('slug', $value)->firstOrFail();
            });
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (required for signed URL validation behind reverse proxy)
        $middleware->trustProxies(at: '*');

        // Redirect expired sessions to Filament login (prevents Route [login] 500)
        $middleware->redirectGuestsTo('/admin/login');

        // Register middleware aliases
        $middleware->alias([
            'partner.auth' => \App\Http\Middleware\PartnerAuthMiddleware::class,
            'partner.audit' => \App\Http\Middleware\PartnerAuditMiddleware::class,
            'optional.auth' => \App\Http\Middleware\OptionalAuth::class,
        ]);

        // Apply SetLocaleFromHeader and DetectUserCurrency middleware to API routes
        $middleware->api(append: [
            \App\Http\Middleware\SetLocaleFromHeader::class,
            \App\Http\Middleware\DetectUserCurrency::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
