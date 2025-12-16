<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleFromHeader
{
    /**
     * Supported locales for the application.
     */
    private array $supportedLocales = ['en', 'fr'];

    /**
     * Default locale.
     */
    private string $defaultLocale = 'en';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get locale from Accept-Language header
        $locale = $request->header('Accept-Language', $this->defaultLocale);

        // Extract just the language code (e.g., 'en' from 'en-US')
        $locale = strtolower(substr($locale, 0, 2));

        // Validate locale is supported
        if (! in_array($locale, $this->supportedLocales)) {
            $locale = $this->defaultLocale;
        }

        // Set application locale
        app()->setLocale($locale);

        return $next($request);
    }
}
