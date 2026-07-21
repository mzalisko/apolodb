<?php

namespace Tests\Feature;

use App\Services\CredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class HeartbeatRejectTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_missing_signature_header_is_rejected(): void
    {
        $site = $this->makeSite();

        $this->sendHeartbeat($site->site_identifier, null, ['headers' => ['X-DB-Signature' => null]])
            ->assertStatus(401);

        $this->assertSiteStillPending($site->id);
    }

    public function test_unknown_site_id_is_rejected(): void
    {
        $this->sendHeartbeat('sid_unknown_site_000000000000000000000000000000000000000000')
            ->assertStatus(401);
    }

    public function test_bad_signature_is_rejected_and_status_unchanged(): void
    {
        $site = $this->makeSite();

        $this->sendHeartbeat($site->site_identifier, null, ['signSecret' => 'the-wrong-secret'])
            ->assertStatus(401);

        $this->assertSiteStillPending($site->id);
        $this->assertDatabaseMissing('event_log_entries', [
            'site_id' => $site->id,
            'event_type' => 'status_changed',
        ]);
    }

    public function test_revoked_credential_is_rejected(): void
    {
        $secret = $this->testSecret;
        $site = $this->makeSite($secret);

        CredentialService::revoke($site);

        $this->sendHeartbeat($site->site_identifier, $secret)
            ->assertStatus(403);

        $this->assertSiteStillPending($site->id);
    }

    public function test_error_body_is_neutral(): void
    {
        $site = $this->makeSite();

        $response = $this->sendHeartbeat($site->site_identifier, null, ['signSecret' => 'wrong']);

        $response->assertStatus(401)->assertJson(['error' => 'unauthorized', 'message' => 'Запит відхилено.']);
        // Жодних натяків на топологію/інші сайти.
        $this->assertArrayNotHasKey('site', $response->json());
    }

    private function assertSiteStillPending(int $siteId): void
    {
        $this->assertDatabaseHas('site_statuses', ['site_id' => $siteId, 'status' => 'pending']);
    }
}
