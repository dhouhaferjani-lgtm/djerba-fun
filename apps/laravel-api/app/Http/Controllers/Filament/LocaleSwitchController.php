<?php

declare(strict_types=1);

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controller to handle locale switching for Filament panels.
 */
class LocaleSwitchController extends Controller
{
    /**
     * Switch the Filament panel locale.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        // Only allow supported locales
        if (in_array($locale, ['en', 'fr'])) {
            session(['filament_locale' => $locale]);
        }

        // Redirect back to previous page
        return redirect()->back();
    }
}
