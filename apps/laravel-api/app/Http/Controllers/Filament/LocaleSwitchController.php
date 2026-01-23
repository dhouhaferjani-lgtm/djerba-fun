<?php

declare(strict_types=1);

namespace App\Http\Controllers\Filament;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleSwitchController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['en', 'fr'])) {
            session(['filament_locale' => $locale]);
            session()->save(); // Explicitly save session before redirect
        }

        return redirect()->back();
    }
}
