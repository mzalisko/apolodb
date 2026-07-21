<?php

namespace Tests\Feature;

use App\Models\SiteCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class HeartbeatAcceptTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_valid_signed_heartbeat_is_accepted_and_marks_site_online(): void
    {
        $site = $this->makeSite();

        $response = $this->sendHeartbeat($site->site_identifier);

        $response->assertStatus(202)->assertJson(['accepted' => true]);

        // QUEUE=sync -> job виконано під час запиту: сайт online, last_seen встановлено.
        $this->assertDatabaseHas('site_statuses', [
            'site_id' => $site->id,
            'status' => 'online',
        ]);
        $this->assertNotNull($site->status()->first()->last_seen_at);

        // credential.last_used_at оновлено.
        $this->assertNotNull(SiteCredential::find($site->active_credential_id)->last_used_at);

        // Аудит транзиції pending->online.
        $this->assertDatabaseHas('event_log_entries', [
            'site_id' => $site->id,
            'event_type' => 'status_changed',
            'new_value' => 'online',
        ]);
    }

    public function test_response_leaks_no_site_data(): void
    {
        $site = $this->makeSite();

        $response = $this->sendHeartbeat($site->site_identifier);

        // Відповідь — лише {accepted:true}, без статусу/ідентифікаторів (FR-019/FR-032).
        $this->assertSame(['accepted' => true], $response->json());
    }
}
