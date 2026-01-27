<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust all proxies (required for signed URL validation behind reverse proxy)
        $middleware->trustProxies(at: '*');

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
