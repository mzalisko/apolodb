<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class DetectOfflineTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_online_site_past_window_flips_to_offline_with_audit(): void
    {
        $site = $this->makeSite();
        $window = (int) config('databridge.offline_window', 300);

        $site->status()->update([
            'status' => 'online',
            'last_seen_at' => now()->subSeconds($window + 60),
            'last_status_change_at' => now()->subMinutes(10),
        ]);

        Artisan::call('sites:detect-offline');

        $this->assertDatabaseHas('site_statuses', ['site_id' => $site->id, 'status' => 'offline']);
        $this->assertDatabaseHas('event_log_entries', [
            'site_id' => $site->id,
            'event_type' => 'status_changed',
            'old_value' => 'online',
            'new_value' => 'offline',
        ]);
    }

    public function test_recently_seen_site_stays_online(): void
    {
        $site = $this->makeSite();
        $site->status()->update(['status' => 'online', 'last_seen_at' => now()->subSeconds(30)]);

        Artisan::call('sites:detect-offline');

        $this->assertDatabaseHas('site_statuses', ['site_id' => $site->id, 'status' => 'online']);
    }

    public function test_pending_site_is_not_flipped_offline(): void
    {
        $site = $this->makeSite(); // pending, last_seen null

        Artisan::call('sites:detect-offline');

        $this->assertDatabaseHas('site_statuses', ['site_id' => $site->id, 'status' => 'pending']);
    }
}
