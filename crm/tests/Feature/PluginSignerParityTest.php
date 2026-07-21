<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\SiteCredential;
use App\Services\HmacVerifier;
use App\Support\CanonicalRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Інтеграція плагін↔CRM (US2, обидві половини системи).
 *
 * Використовує РЕАЛЬНИЙ підписувач плагіна (plugin/includes/class-sd-signer.php, змонтований
 * у /plugin) і доводить, що його підпис приймає справжній бекенд — не тест-дублікат.
 * Це закриває ризик розбіжності канонік/HMAC між двома кодовими базами (Принцип I).
 */
class PluginSignerParityTest extends TestCase
{
    use RefreshDatabase;

    private string $secret = 'e2e_secret_0123456789abcdef0123456789abcdef0123456789abcdef0123';

    protected function setUp(): void
    {
        parent::setUp();

        // Константи, які плагін очікує від WordPress-оточення (data-site.php).
        defined('ABSPATH') || define('ABSPATH', '/');
        defined('SD_SIG_VERSION') || define('SD_SIG_VERSION', 'v1');
        defined('SD_HEARTBEAT_PATH') || define('SD_HEARTBEAT_PATH', '/v1/heartbeat');

        if (! is_file('/plugin/includes/class-sd-signer.php')) {
            $this->markTestSkipped('Каталог plugin/ не змонтовано в контейнер (./plugin:/plugin).');
        }

        require_once '/plugin/includes/class-sd-signer.php';
    }

    /** Канонік і підпис плагіна мають збігатися з бекенд-білдерами байт-у-байт. */
    public function test_plugin_signer_matches_backend(): void
    {
        $args = ['POST', '/v1/heartbeat', '{"site_id":"s1","status":"online","timestamp":1700000000,"nonce":"n"}', 's1', 1_700_000_000, 'n'];

        $pluginCanonical = \SD_Signer::canonical(...$args);
        $backendCanonical = CanonicalRequest::build('v1', ...$args);

        $this->assertSame($backendCanonical, $pluginCanonical, 'Канонічний рядок плагіна ≠ бекенду');
        $this->assertSame(
            HmacVerifier::sign($backendCanonical, $this->secret),
            \SD_Signer::signature($pluginCanonical, $this->secret),
            'Підпис плагіна ≠ бекенду'
        );
    }

    /** Heartbeat, підписаний РЕАЛЬНИМ кодом плагіна, приймається бекендом і робить сайт online. */
    public function test_heartbeat_signed_by_real_plugin_is_accepted(): void
    {
        $site = $this->makeE2ESite();
        $ts = time();
        $nonce = \SD_Signer::nonce();
        $body = json_encode([
            'site_id' => $site->site_identifier,
            'status' => 'online',
            'timestamp' => $ts,
            'nonce' => $nonce,
        ]);

        $canonical = \SD_Signer::canonical('POST', '/v1/heartbeat', $body, $site->site_identifier, $ts, $nonce);
        $signature = \SD_Signer::signature($canonical, $this->secret);

        $this->call('POST', '/v1/heartbeat', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_DB_SITE_ID' => $site->site_identifier,
            'HTTP_X_DB_TIMESTAMP' => (string) $ts,
            'HTTP_X_DB_NONCE' => $nonce,
            'HTTP_X_DB_SIGNATURE' => $signature,
            'HTTP_X_DB_SIG_VERSION' => 'v1',
        ], $body)
            ->assertStatus(202)
            ->assertJson(['accepted' => true]);

        $this->assertSame('online', $site->fresh()->status->status);
    }

    private function makeE2ESite(): Site
    {
        $site = Site::factory()->create();
        $site->status()->create(['status' => 'pending', 'last_status_change_at' => now(), 'updated_at' => now()]);
        $cred = SiteCredential::create([
            'site_id' => $site->id,
            'secret_encrypted' => $this->secret,
            'sig_version' => 'v1',
            'state' => 'active',
            'issued_at' => now(),
        ]);
        $site->update(['active_credential_id' => $cred->id]);

        return $site->fresh();
    }
}
