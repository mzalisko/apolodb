<?php

namespace App\Console\Commands;

use App\Jobs\ProcessHeartbeat;
use App\Models\Site;
use App\Models\SiteCredential;
use App\Models\SiteStatus;
use App\Services\HmacVerifier;
use App\Services\NonceStore;
use App\Support\CanonicalRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Queue;

/**
 * SC-007 / T060 — навантажувальна перевірка приймання.
 *
 * Генерує N сайтів і надсилає на кожен справжній ПІДПИСАНИЙ heartbeat, повторюючи
 * авторитетну бекенд-роботу з HeartbeatController (HMAC-verify + nonce-claim + dispatch
 * у Redis-чергу). Далі Horizon-воркери дренують чергу — вимірюємо приймання й обробку.
 */
class LoadTest extends Command
{
    protected $signature = 'databridge:loadtest
        {--sites=500 : Скільки сайтів (верхнього рівня) задіяти}
        {--rounds=1 : Скільки heartbeat-раундів на сайт (кожен ≤ ліміту/хв)}
        {--wait : Чекати, поки Horizon дренує чергу, і виміряти обробку}
        {--timeout=180 : Максимум секунд очікування дренажу}';

    protected $description = 'SC-007: генерує N підписаних heartbeat-ів і вимірює приймання/дренаж черги';

    /** Відомий секрет для навантажувальних сайтів (НЕ для проду). */
    private string $secret = 'loadtest_secret_0123456789abcdef0123456789abcdef0123456789abcdef01';

    public function handle(): int
    {
        $n = max(1, (int) $this->option('sites'));
        $rounds = max(1, (int) $this->option('rounds'));

        $this->info("▶ Готую $n навантажувальних сайтів…");
        $sites = $this->ensureSites($n);
        $ids = array_column($sites, 'siteId');

        $sigVersion = (string) config('databridge.sig_version', 'v1');
        $path = (string) config('databridge.heartbeat_path', '/v1/heartbeat');

        $before = Queue::size();
        $accepted = 0;
        $start = microtime(true);

        for ($r = 0; $r < $rounds; $r++) {
            foreach ($sites as $s) {
                $ts = time();
                $nonce = rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '=');
                $body = json_encode([
                    'site_id' => $s['ident'], 'status' => 'online', 'timestamp' => $ts, 'nonce' => $nonce,
                ]);

                // Дзеркалимо HeartbeatController §4–7 (авторитетна перевірка на бекенді):
                $canonical = CanonicalRequest::build($sigVersion, 'POST', $path, $body, $s['ident'], $ts, $nonce);
                $signature = HmacVerifier::sign($canonical, $this->secret);

                if (! HmacVerifier::verify($signature, $canonical, $this->secret)) {
                    continue;
                }
                if (! NonceStore::claim($s['ident'], $nonce)) {
                    continue; // replay (унікальні nonce → не має спрацьовувати)
                }
                ProcessHeartbeat::dispatch($s['siteId'], $s['credId'], $ts);
                $accepted++;
            }
        }

        $enqSecs = max(0.001, microtime(true) - $start);
        $this->newLine();
        $this->info(sprintf(
            '✔ Приймання: %d heartbeat (%d×%d) за %.2f с → %s heartbeat/с',
            $accepted, count($sites), $rounds, $enqSecs, number_format($accepted / $enqSecs, 0)
        ));
        $this->line(sprintf('  Черга Redis: %d → %d', $before, Queue::size()));

        if (! $this->option('wait')) {
            $this->line('  (запусти Horizon і додай --wait, щоб виміряти дренаж)');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('▶ Чекаю, поки Horizon-воркери зʼїдять чергу…');
        $drainStart = microtime(true);
        $timeout = (int) $this->option('timeout');
        $target = count($sites);

        do {
            usleep(300_000);
            $size = Queue::size();
            $online = (int) SiteStatus::whereIn('site_id', $ids)->where('status', 'online')->count();
            $elapsed = microtime(true) - $drainStart;
            $this->output->write(sprintf("\r  черга: %-5d | online: %-5d/%d | %.1f с   ", $size, $online, $target, $elapsed));
        } while (($size > 0 || $online < $target) && $elapsed < $timeout);

        $this->newLine(2);
        $processed = $accepted - Queue::size();
        $online = (int) SiteStatus::whereIn('site_id', $ids)->where('status', 'online')->count();
        $drainSecs = max(0.001, microtime(true) - $drainStart);

        $this->info(sprintf('✔ Дренаж: %d оброблено за %.2f с → %s job/с', $processed, $drainSecs, number_format($processed / $drainSecs, 0)));
        $this->line(sprintf('  Online-сайтів: %d/%d · залишок у черзі: %d', $online, $target, Queue::size()));

        $ok = Queue::size() === 0 && $online >= $target;
        $this->newLine();
        $this->{$ok ? 'info' : 'error'}($ok
            ? "✅ SC-007: $target сайтів прийнято й оброблено без деградації."
            : "⚠ SC-007: не всі сайти оброблено в межах timeout — див. метрики вище.");

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** Ідемпотентно створює N навантажувальних сайтів з відомим секретом. */
    private function ensureSites(int $n): array
    {
        $out = [];
        for ($i = 1; $i <= $n; $i++) {
            $domain = "loadtest-$i.local";
            $site = Site::firstWhere('domain', $domain);

            if (! $site) {
                $site = Site::create([
                    'name' => "LoadTest $i",
                    'domain' => $domain,
                    'site_identifier' => 'lt_'.str_pad((string) $i, 6, '0', STR_PAD_LEFT).'_'.bin2hex(random_bytes(5)),
                ]);
                $site->status()->create([
                    'status' => 'pending', 'last_status_change_at' => now(), 'updated_at' => now(),
                ]);
                $cred = SiteCredential::create([
                    'site_id' => $site->id,
                    'secret_encrypted' => $this->secret,
                    'sig_version' => 'v1',
                    'state' => 'active',
                    'issued_at' => now(),
                ]);
                $site->update(['active_credential_id' => $cred->id]);
                $site->refresh();
            }

            $out[] = ['ident' => $site->site_identifier, 'siteId' => $site->id, 'credId' => $site->active_credential_id];

            if ($i % 100 === 0) {
                $this->line("  …$i сайтів готово");
            }
        }

        return $out;
    }
}
