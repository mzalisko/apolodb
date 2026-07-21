<?php

namespace App\Services;

/**
 * Авторитетна HMAC-верифікація (виключно на бекенді, FR-027/028/029).
 * Проксі секрету не тримає й HMAC не перевіряє.
 */
class HmacVerifier
{
    /** Значення заголовка X-DB-Signature для каноніка й секрету. */
    public static function sign(string $canonical, string $secret): string
    {
        return 'sha256='.hash_hmac('sha256', $canonical, $secret);
    }

    /** Constant-time порівняння наданого підпису з очікуваним (блокує timing-атаки). */
    public static function verify(string $provided, string $canonical, string $secret): bool
    {
        $expected = self::sign($canonical, $secret);

        return hash_equals($expected, $provided);
    }
}
