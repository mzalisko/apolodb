<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteStatus;
use App\Services\CredentialService;
use App\Services\DomainNormalizer;
use App\Services\EventLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

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

    /** §3.3 Список сайтів зі статусом + часом оновлення, фільтр «N із M» (FR-015/016/017/018). */
    public function index(Request $request): Response
    {
        $filter = $request->query('status');
        $perPage = max(1, (int) $request->query('per_page', 50));
        $page = max(1, (int) $request->query('page', 1));

        $byStatus = SiteStatus::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $total = (int) Site::count();

        $query = Site::query()->with('status');
        if ($filter) {
            $query->whereHas('status', fn ($q) => $q->where('status', $filter));
        }
        $filtered = $filter ? (int) (clone $query)->count() : $total;

        $sites = $query->orderBy('name')->forPage($page, $perPage)->get();

        $payload = [
            'counts' => [
                'total' => $total,
                'filtered' => $filtered,
                'by_status' => [
                    'online' => (int) ($byStatus['online'] ?? 0),
                    'pending' => (int) ($byStatus['pending'] ?? 0),
                    'offline' => (int) ($byStatus['offline'] ?? 0),
                    'inactive' => (int) ($byStatus['inactive'] ?? 0),
                ],
            ],
            'sites' => $sites->map(fn (Site $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'domain' => $s->domain,
                'status' => $s->status?->status,
                'last_seen_at' => $s->status?->last_seen_at?->toIso8601String(),
                'token_state' => $s->active_credential_id ? 'active' : 'revoked',
            ])->values()->all(),
            'page' => $page,
            'per_page' => $perPage,
        ];

        if (! $request->wantsJson()) {
            return response()->view('sites.index', ['payload' => $payload, 'filter' => $filter]);
        }

        return response()->json($payload);
    }

    /** §3.4 Ручне вимкнення сайту → статус inactive досяжний (FR-013/016). */
    public function deactivate(Request $request, Site $site): JsonResponse
    {
        $old = $site->status?->status;

        $site->update(['deactivated_at' => now()]);
        $site->status()->update([
            'status' => 'inactive',
            'last_status_change_at' => now(),
            'updated_at' => now(),
        ]);

        $actor = $request->user();
        EventLogger::record('site_deactivated', $site, $actor?->email ?? 'system', $actor?->id, 'status', $old, 'inactive');

        return response()->json([
            'id' => $site->id,
            'status' => 'inactive',
            'deactivated_at' => $site->fresh()->deactivated_at?->toIso8601String(),
        ]);
    }

    /** §3.4 Реактивація → pending (чекає наступного heartbeat). */
    public function reactivate(Request $request, Site $site): JsonResponse
    {
        $old = $site->status?->status;

        $site->update(['deactivated_at' => null]);
        $site->status()->update([
            'status' => 'pending',
            'last_status_change_at' => now(),
            'updated_at' => now(),
        ]);

        $actor = $request->user();
        EventLogger::record('site_reactivated', $site, $actor?->email ?? 'system', $actor?->id, 'status', $old, 'pending');

        return response()->json(['id' => $site->id, 'status' => 'pending']);
    }

    /** §3.2 Відкликання секрету (FR-005, SC-006). site-id НЕ змінюється. */
    public function revokeCredential(Request $request, Site $site): JsonResponse
    {
        CredentialService::revoke($site);

        $actor = $request->user();
        EventLogger::record('token_revoked', $site, $actor?->email ?? 'system', $actor?->id, 'credential');

        return response()->json([
            'site_id' => $site->site_identifier,   // незмінний (A-4)
            'token_state' => 'revoked',
            'revoked_at' => now()->toIso8601String(),
        ]);
    }

    /** §3.2 Перевипуск секрету — новий показ ОДИН РАЗ; site-id НЕ змінюється (A-4). */
    public function reissueCredential(Request $request, Site $site): JsonResponse
    {
        $secret = CredentialService::reissue($site);

        $actor = $request->user();
        EventLogger::record('token_reissued', $site, $actor?->email ?? 'system', $actor?->id, 'credential');

        return response()->json([
            'credentials' => [
                'site_id' => $site->fresh()->site_identifier,   // НЕ змінюється
                'signing_secret' => $secret,                    // показ один раз
                'sig_version' => config('databridge.sig_version', 'v1'),
            ],
            'previous_token_state' => 'revoked',
        ], 201);
    }
}
