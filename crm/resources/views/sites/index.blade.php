{{-- Екран списку сайтів (User Story 3). Самодостатній HTML — жодних зовнішніх
     ассетів/CDN (Принцип III). Легенда статусів із дизайну (FR-016). --}}
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DataBridge — Сайти</title>
    <style>
        :root { font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f7f7f8; color: #1b1b1f; }
        header { padding: 16px 24px; border-bottom: 1px solid #e3e3e6; background: #fff; }
        h1 { font-size: 18px; margin: 0; }
        main { padding: 24px; }
        .filters { margin-bottom: 16px; }
        .filters a { margin-right: 8px; text-decoration: none; color: #444; font-size: 14px;
            padding: 4px 10px; border: 1px solid #d5d5d9; border-radius: 6px; }
        .filters a.active { background: #1b1b1f; color: #fff; border-color: #1b1b1f; }
        .counter { color: #666; font-size: 13px; margin-left: 8px; }
        table { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e3e3e6; }
        th, td { text-align: left; padding: 10px 14px; border-bottom: 1px solid #eee; font-size: 14px; }
        th { color: #666; font-weight: 600; font-size: 12px; text-transform: uppercase; }
        td.mono { font-family: ui-monospace, monospace; color: #444; }
        .status { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; }
        .dot { width: 9px; height: 9px; border-radius: 50%; display: inline-block; }
        .s-online { background: #16a34a; }   /* активний — зелений */
        .s-pending { background: #2563eb; }  /* очікує — синій */
        .s-offline { background: #dc2626; }  /* офлайн — червоний */
        .s-inactive { background: #9ca3af; } /* неактивний — сірий */
        .empty { padding: 32px; text-align: center; color: #888; }
    </style>
</head>
<body>
    <header><h1>DataBridge — Сайти</h1></header>
    <main>
        @php($c = $payload['counts'])
        <div class="filters">
            <a href="/admin" class="{{ $filter ? '' : 'active' }}">Усі</a>
            <a href="/admin?status=online" class="{{ $filter === 'online' ? 'active' : '' }}">Онлайн ({{ $c['by_status']['online'] }})</a>
            <a href="/admin?status=pending" class="{{ $filter === 'pending' ? 'active' : '' }}">Очікує ({{ $c['by_status']['pending'] }})</a>
            <a href="/admin?status=offline" class="{{ $filter === 'offline' ? 'active' : '' }}">Офлайн ({{ $c['by_status']['offline'] }})</a>
            <a href="/admin?status=inactive" class="{{ $filter === 'inactive' ? 'active' : '' }}">Неактивні ({{ $c['by_status']['inactive'] }})</a>
            <span class="counter">{{ $c['filtered'] }} із {{ $c['total'] }}</span>
        </div>

        <table>
            <thead>
                <tr><th>Сайт</th><th>Домен</th><th>Статус</th><th>Останнє оновлення</th></tr>
            </thead>
            <tbody>
                @forelse($payload['sites'] as $site)
                    <tr>
                        <td>{{ $site['name'] }}</td>
                        <td class="mono">{{ $site['domain'] }}</td>
                        <td>
                            <span class="status">
                                <span class="dot s-{{ $site['status'] }}"></span>{{ $site['status'] }}
                            </span>
                        </td>
                        <td class="mono">{{ $site['last_seen_at'] ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="empty">Сайтів не знайдено. Скиньте фільтри.</td></tr>
                @endforelse
            </tbody>
        </table>
    </main>
</body>
</html>
