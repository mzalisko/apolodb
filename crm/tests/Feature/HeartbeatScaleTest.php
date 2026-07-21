<?php

namespace Tests\Feature;

use App\Models\SiteStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

/**
 * SC-007 — регрес-гейт масштабу приймання.
 *
 * Не замінює ручний навантажувальний прогон databridge:loadtest (500+ сайтів під
 * конкурентним Horizon), але автоматично охороняє коректність багатосайтового шляху:
 * N різних сайтів звітують → усі приймаються й переходять online без плутанини станів.
 */
class HeartbeatScaleTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_many_distinct_sites_reporting_all_go_online(): void
    {
        $n = 30;
        $sites = [];
        for ($i = 0; $i < $n; $i++) {
            $sites[] = $this->makeSite();   // кожен зі своїм site_identifier + активним секретом
        }

        foreach ($sites as $site) {
            $this->sendHeartbeat($site->site_identifier)->assertStatus(202); // 1/сайт — у межах ліміту
        }

        $ids = collect($sites)->pluck('id');
        $online = SiteStatus::whereIn('site_id', $ids)->where('status', 'online')->count();

        $this->assertSame($n, $online, "усі $n сайтів мають перейти online без деградації станів");
    }
}
