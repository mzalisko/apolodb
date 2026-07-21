<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesSites;
use Tests\TestCase;

class SiteListFilterTest extends TestCase
{
    use MakesSites, RefreshDatabase;

    public function test_filter_by_status_with_n_of_m_counts(): void
    {
        $admin = User::factory()->create();
        foreach (range(1, 3) as $i) {
            $this->makeSiteWithStatus('online');
        }
        foreach (range(1, 2) as $i) {
            $this->makeSiteWithStatus('offline');
        }
        $this->makeSiteWithStatus('pending');

        $response = $this->actingAs($admin)->getJson('/admin/sites?status=offline');

        $response->assertStatus(200)
            ->assertJsonPath('counts.total', 6)         // M
            ->assertJsonPath('counts.filtered', 2)      // N (FR-018)
            ->assertJsonPath('counts.by_status.online', 3)
            ->assertJsonPath('counts.by_status.offline', 2)
            ->assertJsonPath('counts.by_status.pending', 1);

        $sites = $response->json('sites');
        $this->assertCount(2, $sites);
        foreach ($sites as $site) {
            $this->assertSame('offline', $site['status']);
        }
    }

    public function test_no_filter_returns_all(): void
    {
        $admin = User::factory()->create();
        $this->makeSiteWithStatus('online');
        $this->makeSiteWithStatus('pending');

        $this->actingAs($admin)->getJson('/admin/sites')
            ->assertStatus(200)
            ->assertJsonPath('counts.total', 2)
            ->assertJsonPath('counts.filtered', 2);
    }
}
