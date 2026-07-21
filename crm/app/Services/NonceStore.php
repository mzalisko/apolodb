<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Анти-replay сховище nonce + перевірка вікна часу (contract §1.4).
 *
 * Авторитетно лише на бекенді (FR-028). Ключ — (site-id, nonce); TTL = 2×
 * вікна толерантності. `Cache::add` (Redis) атомарний: повертає true лише при
 * першому пред'явленні пари, false — при повторі (replay).
 */
class NonceStore
{
    /** true — nonce свіжий і зафіксований; false — повтор у межах TTL. */
    public static function claim(string $siteId, string $nonce): bool
    {
        $ttl = (int) config('databridge.nonce_ttl', 600);

        return Cache::add(self::key($siteId, $nonce), true, $ttl);
    }

    /** Чи позначка часу в межах ± вікна толерантності від «зараз». */
    public static function timestampFresh(int $timestamp): bool
    {
        $tolerance = (int) config('databridge.timestamp_tolerance', 300);

        return abs(now()->getTimestamp() - $timestamp) <= $tolerance;
    }

    private static function key(string $siteId, string $nonce): string
    {
        return 'sd:nonce:'.$siteId.':'.$nonce;
    }
}
