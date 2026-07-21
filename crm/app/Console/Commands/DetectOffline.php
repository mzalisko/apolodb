<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Models\SiteStatus;
use App\Services\EventLogger;
use Illuminate\Console\Command;

/**
 * Детектор офлайну (FR-014): CRM НЕ пінгує сайти — офлайн визначається тишею.
 * Один set-based UPDATE усіх online-сайтів із last_seen_at за межами вікна.
 */
class DetectOffline extends Command
{
    protected $signature = 'sites:detect-offline';

    protected $description = 'Позначити офлайн сайти без валідного звіту довше за вікно офлайну';

    public function handle(): int
    {
        $window = (int) config('databridge.offline_window', 300);
        $threshold = now()->subSeconds($window);

        $staleIds = SiteStatus::query()
            ->where('status', 'online')
            ->where('last_seen_at', '<', $threshold)
            ->pluck('site_id');

        if ($staleIds->isEmpty()) {
            return self::SUCCESS;
        }

        SiteStatus::query()
            ->whereIn('site_id', $staleIds)
            ->update([
                'status' => 'offline',
                'last_status_change_at' => now(),
                'updated_at' => now(),
            ]);

        // Аудит транзицій online→offline (FR-021).
        foreach (Site::whereIn('id', $staleIds)->get() as $site) {
            EventLogger::record('status_changed', $site, 'system', null, 'status', 'online', 'offline');
        }

        $this->info("Позначено офлайн: {$staleIds->count()}");

        return self::SUCCESS;
    }
}
