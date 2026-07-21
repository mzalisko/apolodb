<?php

namespace Tests\Concerns;

use App\Models\Site;

trait MakesSites
{
    /** Створити сайт із заданим статусом підключення (для тестів списку). */
    protected function makeSiteWithStatus(string $status, array $siteAttrs = []): Site
    {
        $site = Site::factory()->create($siteAttrs);
        $site->status()->create([
            'status' => $status,
            'last_seen_at' => in_array($status, ['online', 'offline'], true) ? now() : null,
            'last_status_change_at' => now(),
            'updated_at' => now(),
        ]);

        return $site;
    }
}
