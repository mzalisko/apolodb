<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesSites;
use Tests\TestCase;

class SiteDeactivateTest extends TestCase
{
    use MakesSites, RefreshDatabase;

    public function test_deactivate_makes_status_inactive_with_audit(): void
    {
        $admin = User::factory()->create();
        $site = $this->makeSiteWithStatus('online');

        $this->actingAs($admin)->postJson("/admin/sites/{$site->id}/deactivate")
            ->assertStatus(200)
            ->assertJsonPath('status', 'inactive');

        $this->assertDatabaseHas('site_statuses', ['site_id' => $site->id, 'status' => 'inactive']);
        $this->assertNotNull($site->fresh()->deactivated_at);
        $this->assertDatabaseHas('event_log_entries', [
            'site_id' => $site->id,
            'event_type' => 'site_deactivated',
            'new_value' => 'inactive',
        ]);
    }

    public function test_reactivate_returns_site_to_pending(): void
    {
        $admin = User::factory()->create();
        $site = $this->makeSiteWithStatus('online');

        $this->actingAs($admin)->postJson("/admin/sites/{$site->id}/deactivate")->assertStatus(200);
        $this->actingAs($admin)->postJson("/admin/sites/{$site->id}/reactivate")
            ->assertStatus(200)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('site_statuses', ['site_id' => $site->id, 'status' => 'pending']);
        $this->assertNull($site->fresh()->deactivated_at);
    }
}
