<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Детектор офлайну (FR-014): щохвилини, set-based UPDATE, без накладання.
Schedule::command('sites:detect-offline')->everyMinute()->withoutOverlapping();
