<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Лише авторизовані активні адміністратори (FR-022, A-5 — для MVP admin-only).
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive() || ! $user->isAdmin()) {
            abort(403, 'Недостатньо прав.');
        }

        return $next($request);
    }
}
