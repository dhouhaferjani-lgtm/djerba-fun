<?php

use App\Http\Controllers\Api\V1\ClictopayCallbackController;
use App\Http\Controllers\Filament\LocaleSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/filament/locale/{locale}', LocaleSwitchController::class)
    ->name('filament.locale.switch')
    ->middleware(['web']);

/*
|--------------------------------------------------------------------------
| Payment Gateway Callbacks
|--------------------------------------------------------------------------
|
| These routes handle redirects from external payment gateways.
| They must be web routes (not API) because they receive browser redirects.
|
*/

// Clictopay SMT callback - handles redirect after payment on Clictopay page
Route::get('/payment/clictopay/callback/{intent}', [ClictopayCallbackController::class, 'callback'])
    ->name('payment.clictopay.callback');
