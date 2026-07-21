<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class RevokeCredentialTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_revoked_secret_is_rejected_and_site_id_unchanged(): void
    {
        $admin = User::factory()->create();
        $secret = $this->testSecret;
        $site = $this->makeSite($secret);
        $siteId = $site->site_identifier;

        // До відкликання — валідний heartbeat приймається.
        $this->sendHeartbeat($siteId, $secret)->assertStatus(202);

        // Відкликання (site-id незмінний, A-4).
        $this->actingAs($admin)->postJson("/admin/sites/{$site->id}/credentials/revoke")
            ->assertStatus(200)
            ->assertJsonPath('token_state', 'revoked')
            ->assertJsonPath('site_id', $siteId);

        // Після відкликання — той самий секрет відхиляється (немає активного credential -> 403, SC-006).
        $this->sendHeartbeat($siteId, $secret)->assertStatus(403);

        $this->assertDatabaseHas('event_log_entries', [
            'site_id' => $site->id,
            'event_type' => 'token_revoked',
        ]);
    }
}
