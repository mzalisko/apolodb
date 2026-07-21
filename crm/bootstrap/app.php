<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: '',   // heartbeat лишається на /v1/heartbeat (без /api)
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // JSON API-ендпоінти (contract §2/§3): без CSRF.
        // - v1/*     — heartbeat: зовнішній, автентифікація підписом HMAC (не сесія).
        // - admin/*  — internal JSON API операторів: session-cookie + EnsureAdmin.
        $middleware->validateCsrfTokens(except: [
            'v1/*',
            'admin/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('v1/*') || $request->is('admin/*'),
        );

        // T017: нейтральні відповіді на v1/* (heartbeat) — без розкриття топології (§2.4, FR-019/FR-032).
        $exceptions->render(function (Throwable $e, Request $request) {
            if ($request->is('v1/*')) {
                $status = $e instanceof HttpExceptionInterface
                    ? $e->getStatusCode()
                    : 500;

                return response()->json(['error' => 'request_rejected', 'message' => 'Запит відхилено.'], $status);
            }
        });
    })->create();
