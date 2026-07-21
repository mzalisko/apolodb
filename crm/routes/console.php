<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Демо-симулятор живого фліту: тримає online-сайти свіжими (last_seen=now).
Schedule::command('databridge:simulate-heartbeats')->everyMinute()->withoutOverlapping();

// Детектор офлайну (FR-014): щохвилини, set-based UPDATE, без накладання.
Schedule::command('sites:detect-offline')->everyMinute()->withoutOverlapping();
