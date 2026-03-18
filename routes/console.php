<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process WhatsApp message queue every minute
Schedule::command('whatsapp:process')->everyMinute();

// Aggregate daily stats at midnight
Schedule::command('stats:daily')->dailyAt('00:05');
