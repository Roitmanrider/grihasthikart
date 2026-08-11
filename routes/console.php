<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pending-orders:process')
    ->everyFiveMinutes()
    ->withoutOverlapping();

Schedule::command('cart-activity:cleanup')
    ->dailyAt('02:10')
    ->withoutOverlapping();

Schedule::command('cart-activity:generate-monthly-risk')
    ->monthlyOn(1, '02:30')
    ->withoutOverlapping();
