<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class ReissueCredentialTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_reissue_gives_new_working_secret_keeping_site_id(): void
    {
        $admin = User::factory()->create();
        $oldSecret = $this->testSecret;
        $site = $this->makeSite($oldSecret);
        $siteId = $site->site_identifier;

        $response = $this->actingAs($admin)->postJson("/admin/sites/{$site->id}/credentials/reissue")
            ->assertStatus(201);

        $newSecret = $response->json('credentials.signing_secret');

        // site-id незмінний (A-4); секрет новий і показ один раз.
        $this->assertSame($siteId, $response->json('credentials.site_id'));
        $this->assertNotSame($oldSecret, $newSecret);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $newSecret);

        // Старий секрет більше не працює (підпис не збігається з новим -> 401).
        $this->sendHeartbeat($siteId, $oldSecret)->assertStatus(401);

        // Новий секрет приймається.
        $this->sendHeartbeat($siteId, $newSecret)->assertStatus(202);

        $this->assertDatabaseHas('event_log_entries', [
            'site_id' => $site->id,
            'event_type' => 'token_reissued',
        ]);
    }
}
