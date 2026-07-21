<?php

namespace Tests\Feature;

use App\Models\SiteStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class HeartbeatIdempotentTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_repeated_heartbeats_are_idempotent_latest_wins(): void
    {
        $site = $this->makeSite();
        $earlier = time() - 30;
        $later = time();

        $this->sendHeartbeat($site->site_identifier, null, ['timestamp' => $earlier])->assertStatus(202);
        $this->sendHeartbeat($site->site_identifier, null, ['timestamp' => $later])->assertStatus(202);

        // Рівно один рядок статусу (1:1), online, last_seen = пізніший звіт.
        $this->assertSame(1, SiteStatus::where('site_id', $site->id)->count());
        $status = $site->status()->first();
        $this->assertSame('online', $status->status);
        $this->assertSame($later, $status->last_seen_at->getTimestamp());
    }

    public function test_out_of_order_heartbeat_does_not_regress_last_seen(): void
    {
        $site = $this->makeSite();
        $later = time();
        $earlier = time() - 60;

        $this->sendHeartbeat($site->site_identifier, null, ['timestamp' => $later])->assertStatus(202);
        $this->sendHeartbeat($site->site_identifier, null, ['timestamp' => $earlier])->assertStatus(202);

        // last_seen лишається пізнішим (GREATEST).
        $this->assertSame($later, $site->status()->first()->last_seen_at->getTimestamp());
    }
}
