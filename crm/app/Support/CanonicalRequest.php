<?php

namespace App\Support;

/**
 * Побудова канонічного рядка підпису.
 *
 * ЄДИНЕ джерело істини — contracts/ingest-contract.md §1.2. МАЄ давати
 * ідентичний рядок байт-у-байт із плагіном (plugin/includes/class-sd-signer.php),
 * інакше підпис не збіжиться (Конституція, Принцип I).
 *
 *   sig_version \n UPPER(METHOD) \n path \n LOWERHEX(SHA-256(raw_body)) \n site-id \n timestamp \n nonce
 */
class CanonicalRequest
{
    public static function build(
        string $sigVersion,
        string $method,
        string $path,
        string $rawBody,
        string $siteId,
        int $timestamp,
        string $nonce
    ): string {
        return implode("\n", [
            $sigVersion,
            strtoupper($method),
            $path,
            hash('sha256', $rawBody), // lowercase hex дайджест точних байтів тіла
            $siteId,
            (string) $timestamp,
            $nonce,
        ]);
    }
}
