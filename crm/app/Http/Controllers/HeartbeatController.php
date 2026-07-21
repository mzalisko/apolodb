<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessHeartbeat;
use App\Models\Site;
use App\Services\HmacVerifier;
use App\Support\CanonicalRequest;
use App\Services\NonceStore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ingest heartbeat (contract §2). Авторитетна верифікація рівня 2 (§1.5, FR-028):
 * lookup site-id → active credential → HMAC (constant-time) → вікно часу → nonce →
 * dispatch у чергу → 202. Секрети й перевірка — виключно тут (не на проксі).
 */
class HeartbeatController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $siteId = (string) $request->header('X-DB-Site-Id', '');
        $timestampHeader = (string) $request->header('X-DB-Timestamp', '');
        $nonce = (string) $request->header('X-DB-Nonce', '');
        $signature = (string) $request->header('X-DB-Signature', '');
        $sigVersion = (string) $request->header('X-DB-Sig-Version', '');
        $rawBody = $request->getContent();

        // 1. Дешеві несекретні перевірки формату/присутності.
        if ($siteId === '' || $nonce === '' || $signature === '' || $sigVersion === '' || ! ctype_digit($timestampHeader)) {
            return $this->reject(401);
        }

        if (strlen($rawBody) > (int) config('databridge.max_body_bytes', 8192)) {
            return $this->reject(413);
        }

        $timestamp = (int) $timestampHeader;

        // 2. Вікно толерантності часу (FR-011).
        if (! NonceStore::timestampFresh($timestamp)) {
            return $this->reject(401);
        }

        // 3. Пошук сайту й активного секрету.
        $site = Site::where('site_identifier', $siteId)->first();
        if (! $site) {
            return $this->reject(401);
        }

        $credential = $site->activeCredential;
        if (! $credential || ! $credential->isActive()) {
            return $this->reject(403); // токен відкликано/відсутній (FR-005)
        }

        // 4. Авторитетна HMAC-верифікація (constant-time) над переобчисленим каноніком.
        $canonical = CanonicalRequest::build(
            $sigVersion,
            'POST',
            (string) config('databridge.heartbeat_path', '/v1/heartbeat'),
            $rawBody,
            $siteId,
            $timestamp,
            $nonce
        );

        if (! HmacVerifier::verify($signature, $canonical, $credential->secret())) {
            return $this->reject(401);
        }

        // 5. Анти-replay: клеймимо nonce ПІСЛЯ перевірки підпису (щоб несигноване не вичерпувало стор).
        if (! NonceStore::claim($siteId, $nonce)) {
            return $this->reject(401); // replay
        }

        // 6. Схема тіла (лише status=online, site_id збігається).
        $payload = json_decode($rawBody, true);
        if (! is_array($payload)
            || ($payload['status'] ?? null) !== 'online'
            || ($payload['site_id'] ?? null) !== $siteId) {
            return $this->reject(400);
        }

        // 7. Асинхронна обробка (FR-010, Принцип IV) — приймання підтверджується одразу.
        ProcessHeartbeat::dispatch($site->id, $credential->id, $timestamp);

        return response()->json(['accepted' => true], 202);
    }

    /** Узагальнена нейтральна відповідь (contract §2.4, FR-019/FR-032). */
    private function reject(int $status): JsonResponse
    {
        $codes = [
            400 => 'bad_request',
            401 => 'unauthorized',
            403 => 'forbidden',
            413 => 'payload_too_large',
            429 => 'rate_limited',
            503 => 'unavailable',
        ];

        return response()->json([
            'error' => $codes[$status] ?? 'request_rejected',
            'message' => 'Запит відхилено.',
        ], $status);
    }
}
