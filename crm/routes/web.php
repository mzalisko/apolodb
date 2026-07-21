<?php

use App\Http\Controllers\Admin\SiteController;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\EnsureAdmin;
use App\Models\Group;
use App\Models\Site;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin'));

// Логін оператора (сесія + CSRF).
Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

/*
 * CRM-internal admin операції (contract §3). Лише авторизовані оператори (FR-022).
 * Session-cookie + EnsureAdmin; CSRF-виняток для admin/* (JSON API) у bootstrap/app.php.
 */
Route::middleware(EnsureAdmin::class)->prefix('admin')->group(function () {
    Route::get('/', [SiteController::class, 'index']);                    // §3.3 US3 (Blade-сторінка)
    Route::view('/sites/create', 'sites.create');                        // US1 форма реєстрації
    Route::get('/sites/{site}/credentials', fn (Site $site) => view('sites.credentials', ['site' => $site])); // US4 керування токеном
    Route::post('/sites', [SiteController::class, 'register']);           // §3.1 US1
    Route::get('/sites', [SiteController::class, 'index']);               // §3.3 US3 (JSON API)
    Route::post('/sites/{site}/deactivate', [SiteController::class, 'deactivate']);   // §3.4 US3
    Route::post('/sites/{site}/reactivate', [SiteController::class, 'reactivate']);   // §3.4 US3
    Route::post('/sites/{site}/credentials/revoke', [SiteController::class, 'revokeCredential']);   // §3.2 US4
    Route::post('/sites/{site}/credentials/reissue', [SiteController::class, 'reissueCredential']); // §3.2 US4
    Route::post('/sites/{site}/favorite', [SiteController::class, 'toggleFavorite']);               // Обране (сайт)
    Route::post('/groups/{group}/favorite', [SiteController::class, 'toggleGroupFavorite']);        // Обране (група)
    Route::get('/groups/data', [SiteController::class, 'groupsData']);                              // дані модалки «Групи»
    Route::post('/groups', [SiteController::class, 'createGroup']);                                 // створити групу
    Route::post('/sites/{site}/groups/{group}/toggle', [SiteController::class, 'toggleSiteGroup']); // членство сайту в групі
});
