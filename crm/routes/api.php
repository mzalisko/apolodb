<?php

use App\Http\Controllers\HeartbeatController;
use App\Http\Middleware\ThrottleBySiteId;
use Illuminate\Support\Facades\Route;

/*
 * Публічний ingest heartbeat (contract §2). Група `api` — без сесії/CSRF;
 * автентифікація виключно підписом HMAC (рівень-2 у контролері).
 */
Route::post('/v1/heartbeat', HeartbeatController::class)->middleware(ThrottleBySiteId::class);
