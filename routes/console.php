<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// schedule handle promotions expire and activate
Schedule::command('promotions:expire')
    ->dailyAt('00:00')
    ->after(function () {
        Artisan::call('promotions:activate');
    });

// schedule check pending orders
Schedule::command('check:pending-orders')
        ->dailyAt('00:00');
