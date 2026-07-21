<?php

namespace App\Services;

use App\Models\Site;
use App\Models\SiteCredential;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Життєвий цикл облікових даних сайту (FR-002..FR-005, A-4).
 *
 * Публічний site_identifier — стабільний (на sites, не змінюється при ротації).
 * Секрет — 256-bit CSPRNG, encrypted-at-rest, показ один раз (повертається plaintext).
 */
class CredentialService
{
    /** Публічний непрозорий high-entropy ідентифікатор сайту. */
    public static function generateSiteIdentifier(): string
    {
        return 'sid_'.Str::random(48);
    }

    /** Секретний ключ підпису — 256-bit CSPRNG (hex). */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Видати новий активний секрет для сайту. Повертає PLAINTEXT (показ один раз).
     */
    public static function issue(Site $site): string
    {
        $secret = self::generateSecret();

        $credential = new SiteCredential([
            'site_id' => $site->id,
            'secret_encrypted' => $secret, // шифрується cast-ом при збереженні
            'sig_version' => config('databridge.sig_version', 'v1'),
            'state' => 'active',
            'issued_at' => now(),
        ]);
        $credential->save();

        $site->active_credential_id = $credential->id;
        $site->save();

        return $secret;
    }

    /** Відкликати поточний активний секрет (FR-005). */
    public static function revoke(Site $site): void
    {
        DB::transaction(function () use ($site) {
            // Серіалізація конкурентних дій над одним сайтом (edge case, T052).
            Site::whereKey($site->id)->lockForUpdate()->first();

            SiteCredential::where('site_id', $site->id)
                ->where('state', 'active')
                ->update(['state' => 'revoked', 'revoked_at' => now()]);

            $site->active_credential_id = null;
            $site->save();
        });
    }

    /**
     * Ротація: відкликати поточний + видати новий. site_identifier НЕ змінюється (A-4).
     * Повертає PLAINTEXT нового секрету (показ один раз).
     */
    public static function reissue(Site $site): string
    {
        return DB::transaction(function () use ($site) {
            Site::whereKey($site->id)->lockForUpdate()->first(); // серіалізація (T052)
            self::revoke($site);

            return self::issue($site->fresh());
        });
    }
}
