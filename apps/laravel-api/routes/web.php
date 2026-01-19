<?php

use App\Http\Controllers\Filament\LocaleSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Filament locale switcher route
Route::get('/filament/locale/{locale}', LocaleSwitchController::class)
    ->name('filament.locale.switch')
    ->middleware(['web']);
