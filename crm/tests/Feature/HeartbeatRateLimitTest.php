<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\SignsHeartbeats;
use Tests\TestCase;

/**
 * FR-026 — незалежний бекенд-rate-limit (2-й контур, per site-id).
 * Раніше не мав тесту (adversarial verify 2026-07-21) — закриває регрес-прогалину.
 *
 * УВАГА: phpunit.xml навмисно вимикає throttle (DB_RATE_BACKEND_PER_MIN дуже великий),
 * щоб інші heartbeat-тести не впиралися в ліміт. Тому тут вмикаємо малий ліміт локально.
 */
class HeartbeatRateLimitTest extends TestCase
{
    use RefreshDatabase, SignsHeartbeats;

    private const LIMIT = 3;

    protected function setUp(): void
    {
        parent::setUp();
        config()->set('databridge.rate_limit.backend_per_minute', self::LIMIT);
    }

    public function test_backend_rate_limit_returns_429_over_per_site_limit(): void
    {
        $site = $this->makeSite();

        // До ліміту включно — приймається.
        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->sendHeartbeat($site->site_identifier)->assertStatus(202);
        }

        // За лімітом — 429, нейтральне тіло (FR-032) і Retry-After.
        $over = $this->sendHeartbeat($site->site_identifier);
        $over->assertStatus(429)
            ->assertJson(['error' => 'rate_limited', 'message' => 'Забагато запитів.']);
        $this->assertNotEmpty($over->headers->get('Retry-After'));
    }

    public function test_rate_limit_is_scoped_per_site_not_global(): void
    {
        $siteA = $this->makeSite();
        $siteB = $this->makeSite();

        // Вичерпуємо ліміт сайту A.
        for ($i = 0; $i < self::LIMIT; $i++) {
            $this->sendHeartbeat($siteA->site_identifier)->assertStatus(202);
        }
        $this->sendHeartbeat($siteA->site_identifier)->assertStatus(429);

        // Сайт B не зачеплений — ліміт саме per site-id, а не глобальний (FR-026).
        $this->sendHeartbeat($siteB->site_identifier)->assertStatus(202);
    }
}
