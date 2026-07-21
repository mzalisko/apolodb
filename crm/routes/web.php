<?php

use App\Http\Controllers\Admin\SiteController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/*
 * CRM-internal admin операції (contract §3). Лише авторизовані оператори (FR-022).
 * Session-cookie + EnsureAdmin; CSRF-виняток для admin/* (JSON API) у bootstrap/app.php.
 */
Route::middleware(EnsureAdmin::class)->prefix('admin')->group(function () {
    Route::post('/sites', [SiteController::class, 'register']);           // §3.1 US1
    Route::get('/sites', [SiteController::class, 'index']);               // §3.3 US3
    Route::post('/sites/{site}/deactivate', [SiteController::class, 'deactivate']);   // §3.4 US3
    Route::post('/sites/{site}/reactivate', [SiteController::class, 'reactivate']);   // §3.4 US3
    Route::post('/sites/{site}/credentials/revoke', [SiteController::class, 'revokeCredential']);   // §3.2 US4
    Route::post('/sites/{site}/credentials/reissue', [SiteController::class, 'reissueCredential']); // §3.2 US4
});
