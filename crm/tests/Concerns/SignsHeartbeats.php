<?php

namespace Tests\Concerns;

use App\Models\Site;
use App\Models\SiteCredential;
use App\Services\HmacVerifier;
use App\Support\CanonicalRequest;
use Illuminate\Testing\TestResponse;

/**
 * Хелпери для тестів heartbeat: створення сайту з відомим секретом і побудова
 * підписаного запиту (дзеркалить плагін + contract §1).
 */
trait SignsHeartbeats
{
    protected string $testSecret = 'test_secret_0123456789abcdef0123456789abcdef0123456789abcdef012345';

    /** Створити сайт зі статусом pending та активним секретом (повертає Site). */
    protected function makeSite(?string $secret = null): Site
    {
        $secret ??= $this->testSecret;

        $site = Site::factory()->create();
        $site->status()->create([
            'status' => 'pending',
            'last_status_change_at' => now(),
            'updated_at' => now(),
        ]);
        $credential = SiteCredential::create([
            'site_id' => $site->id,
            'secret_encrypted' => $secret,
            'sig_version' => 'v1',
            'state' => 'active',
            'issued_at' => now(),
        ]);
        $site->update(['active_credential_id' => $credential->id]);

        return $site->fresh();
    }

    protected function b64url(string $bin): string
    {
        return rtrim(strtr(base64_encode($bin), '+/', '-_'), '=');
    }

    /**
     * Надіслати підписаний heartbeat. $opts: timestamp, nonce, status, signSecret,
     * headerSiteId, bodySiteId, headers (масив; null-значення видаляють заголовок).
     */
    protected function sendHeartbeat(string $siteId, ?string $secret = null, array $opts = []): TestResponse
    {
        $secret ??= $this->testSecret;
        $timestamp = $opts['timestamp'] ?? time();
        $nonce = $opts['nonce'] ?? $this->b64url(random_bytes(16));
        $status = $opts['status'] ?? 'online';
        $bodySiteId = $opts['bodySiteId'] ?? $siteId;

        $body = json_encode([
            'site_id' => $bodySiteId,
            'status' => $status,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
        ]);

        $canonical = CanonicalRequest::build('v1', 'POST', '/v1/heartbeat', $body, $siteId, $timestamp, $nonce);
        $signature = $opts['signature'] ?? HmacVerifier::sign($canonical, $opts['signSecret'] ?? $secret);

        $headers = [
            'X-DB-Site-Id' => $opts['headerSiteId'] ?? $siteId,
            'X-DB-Timestamp' => (string) $timestamp,
            'X-DB-Nonce' => $nonce,
            'X-DB-Signature' => $signature,
            'X-DB-Sig-Version' => 'v1',
        ];
        foreach ($opts['headers'] ?? [] as $key => $value) {
            if ($value === null) {
                unset($headers[$key]);
            } else {
                $headers[$key] = $value;
            }
        }

        $server = ['CONTENT_TYPE' => 'application/json'];
        foreach ($headers as $name => $value) {
            $server['HTTP_'.strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $this->call('POST', '/v1/heartbeat', [], [], [], $server, $body);
    }
}
