<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('games:sync-status')->everyMinute()->withoutOverlapping();
Schedule::command('notifications:dispatch')->everyMinute()->withoutOverlapping()
    ->when(fn () => filled(config('services.webpush.vapid.public_key')) && filled(config('services.webpush.vapid.private_key')));
