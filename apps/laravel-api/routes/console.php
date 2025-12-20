<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cleanup expired booking holds and carts every minute
Schedule::command('booking:cleanup-holds')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
