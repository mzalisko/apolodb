<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Services\CredentialService;
use App\Services\DomainNormalizer;
use App\Services\EventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRM-internal операції над сайтами (contract §3). Лише авторизовані оператори.
 */
class SiteController extends Controller
{
    /** §3.1 Реєстрація сайту + видача облікових даних (FR-001..FR-004, FR-006). */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'domain' => ['required', 'string', 'max:255'],
        ]);

        $domain = DomainNormalizer::normalize($data['domain']);

        if (Site::where('domain', $domain)->exists()) {
            return response()->json([
                'error' => 'domain_already_registered',
                'message' => 'Сайт із таким доменом уже існує.',
            ], 409);
        }

        [$site, $secret] = DB::transaction(function () use ($data, $domain, $request) {
            $site = Site::create([
                'name' => $data['name'],
                'domain' => $domain,
                'site_identifier' => CredentialService::generateSiteIdentifier(),
            ]);

            $site->status()->create([
                'status' => 'pending',
                'last_status_change_at' => now(),
                'updated_at' => now(),
            ]);

            $secret = CredentialService::issue($site);

            $actor = $request->user();
            EventLogger::record('site_registered', $site, $actor?->email ?? 'system', $actor?->id);
            EventLogger::record('token_issued', $site, $actor?->email ?? 'system', $actor?->id, 'credential');

            return [$site->fresh(), $secret];
        });

        return response()->json([
            'site' => [
                'id' => $site->id,
                'name' => $site->name,
                'domain' => $site->domain,
                'status' => 'pending',
                'last_seen_at' => null,
                'created_at' => $site->created_at?->toIso8601String(),
            ],
            'credentials' => [
                'site_id' => $site->site_identifier,       // публічний, несекретний
                'signing_secret' => $secret,               // FR-004: показ ОДИН РАЗ
                'sig_version' => config('databridge.sig_version', 'v1'),
            ],
        ], 201);
    }
}
