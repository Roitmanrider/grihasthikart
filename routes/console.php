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
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

Schedule::command('cart-activity:generate-monthly-risk')
    ->monthlyOn(1, '02:30')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

Schedule::command('inventory:check-low-stock')
    ->everyFifteenMinutes()
    ->withoutOverlapping();

Schedule::command('announcements:cleanup')
    ->dailyAt('02:40')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

Schedule::command('marketing-banners:cleanup')
    ->dailyAt('02:50')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

Schedule::command('prices:cleanup-history')
    ->dailyAt('03:00')
    ->timezone('Asia/Kolkata')
    ->withoutOverlapping();

Schedule::command('ops:scheduler-heartbeat')
    ->everyMinute()
    ->withoutOverlapping();
