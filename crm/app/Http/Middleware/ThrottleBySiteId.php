<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Бекенд-rate-limit (2-й контур, FR-026): ключ = X-DB-Site-Id (НЕ IP —
 * плагіни за спільними хостингами). Проксі тримає 1-й контур.
 */
class ThrottleBySiteId
{
    public function handle(Request $request, Closure $next): Response
    {
        $siteId = (string) $request->header('X-DB-Site-Id', 'unknown');
        $key = 'hb:'.sha1($siteId);
        $max = (int) config('databridge.rate_limit.backend_per_minute', 6);

        if (RateLimiter::tooManyAttempts($key, $max)) {
            return response()->json([
                'error' => 'rate_limited',
                'message' => 'Забагато запитів.',
            ], 429)->header('Retry-After', (string) RateLimiter::availableIn($key));
        }

        RateLimiter::hit($key, 60);

        return $next($request);
    }
}
