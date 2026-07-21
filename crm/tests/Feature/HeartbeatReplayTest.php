<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

class HeartbeatReplayTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    public function test_replayed_nonce_is_rejected(): void
    {
        $site = $this->makeSite();
        $nonce = $this->b64url(random_bytes(16));
        $timestamp = time();

        // Перше пред'явлення — приймається.
        $this->sendHeartbeat($site->site_identifier, null, ['nonce' => $nonce, 'timestamp' => $timestamp])
            ->assertStatus(202);

        // Той самий (валідно підписаний) запит вдруге — відхиляється як replay.
        $this->sendHeartbeat($site->site_identifier, null, ['nonce' => $nonce, 'timestamp' => $timestamp])
            ->assertStatus(401);
    }

    public function test_stale_timestamp_is_rejected(): void
    {
        $site = $this->makeSite();

        $this->sendHeartbeat($site->site_identifier, null, ['timestamp' => time() - 1000])
            ->assertStatus(401);
    }

    public function test_future_timestamp_is_rejected(): void
    {
        $site = $this->makeSite();

        $this->sendHeartbeat($site->site_identifier, null, ['timestamp' => time() + 1000])
            ->assertStatus(401);
    }
}
