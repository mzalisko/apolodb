<?php

namespace App\Jobs;

use App\Models\SiteCredential;
use App\Models\SiteStatus;
use App\Services\EventLogger;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Асинхронна обробка heartbeat (FR-010): ідемпотентний latest-wins upsert у
 * site_statuses. Повтори/гонки дають узгоджений результат.
 */
class ProcessHeartbeat implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $siteId,
        public int $credentialId,
        public int $reportTimestamp,
    ) {}

    public function handle(): void
    {
        $status = SiteStatus::find($this->siteId);
        if (! $status) {
            return;
        }

        // Ручний латч inactive придушує автоматичні транзиції (data-model §2).
        if ($status->status === 'inactive') {
            return;
        }

        $report = Carbon::createFromTimestamp($this->reportTimestamp);

        // last_seen_at = GREATEST(existing, report) — латест-вінс під конкурентністю.
        if (! $status->last_seen_at || $report->gt($status->last_seen_at)) {
            $status->last_seen_at = $report;
        }

        $old = $status->status;
        $transitioned = $old !== 'online';

        if ($transitioned) {
            $status->status = 'online';
            $status->last_status_change_at = now();
        }

        $status->updated_at = now();
        $status->save();

        SiteCredential::whereKey($this->credentialId)->update(['last_used_at' => now()]);

        if ($transitioned) {
            EventLogger::record('status_changed', $status->site, 'system', null, 'status', $old, 'online');
        }
    }
}
