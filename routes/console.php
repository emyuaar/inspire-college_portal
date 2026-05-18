<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Automatically process queued jobs (like assignment submission emails) every minute via the scheduler
\Illuminate\Support\Facades\Schedule::command('queue:work --stop-when-empty')
    ->everyMinute()
    ->withoutOverlapping();
