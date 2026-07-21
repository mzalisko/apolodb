<?php

namespace App\Services;

/**
 * Нормалізація домену перед UNIQUE-перевіркою (FR-006).
 * lowercase, зняття схеми/порту/шляху/кінцевої крапки.
 */
class DomainNormalizer
{
    public static function normalize(string $domain): string
    {
        $d = strtolower(trim($domain));
        $d = preg_replace('#^[a-z]+://#', '', $d);  // прибрати схему
        $d = preg_replace('#/.*$#', '', $d);        // прибрати шлях
        $d = preg_replace('#:\d+$#', '', $d);       // прибрати порт
        $d = rtrim($d, '.');                        // прибрати кінцеву крапку

        return $d;
    }
}
