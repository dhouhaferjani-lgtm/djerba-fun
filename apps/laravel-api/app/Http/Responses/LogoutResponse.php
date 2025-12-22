<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request): RedirectResponse
    {
        // Logout from the web guard first
        Auth::guard('web')->logout();

        // Fully invalidate the session
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Determine redirect URL based on the current panel path
        $path = $request->path();
        if (str_starts_with($path, 'vendor')) {
            return redirect()->to('/vendor/login');
        } elseif (str_starts_with($path, 'admin')) {
            return redirect()->to('/admin/login');
        }

        return redirect()->to('/');
    }
}
