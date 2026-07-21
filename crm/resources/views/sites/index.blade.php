@extends('layouts.app')
@section('title', 'DataBridge — Сайти')
@section('crumbs')<span class="cur">Сайти</span>@endsection
@section('content')
    @php
        $c = $payload['counts'];
        // Канонічний словник станів із дизайну (design.status): колір збігається зі статусом зв'язності.
        $labels = ['online' => 'Активний', 'pending' => 'Очікує', 'offline' => 'Помилка', 'inactive' => 'Неактивний'];
    @endphp

    {{-- ── Панель фільтрів (точно як design/CRM v2.dc.html · рядок 153) ── --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px;padding:3px;background:var(--surface);border:1px solid var(--border);border-radius:9px">
            <button type="button" title="Показати лише обране"
                    style="padding:6px 11px;background:transparent;border:0;border-radius:7px;color:var(--text-dim);font:inherit;font-size:12px;cursor:pointer">★ Обране</button>
        </div>

        <label style="display:flex;flex-direction:column;gap:0;position:relative">
            <select onchange="location.href=this.value"
                    style="padding:8px 30px 8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:9px;color:var(--text);font:inherit;font-size:12.5px;cursor:pointer;min-width:150px;-webkit-appearance:none;appearance:none">
                <option value="/admin" @selected(! $filter)>Статус: усі</option>
                <option value="/admin?status=online" @selected($filter === 'online')>Активні</option>
                <option value="/admin?status=pending" @selected($filter === 'pending')>Очікують</option>
                <option value="/admin?status=offline" @selected($filter === 'offline')>Помилка</option>
                <option value="/admin?status=inactive" @selected($filter === 'inactive')>Неактивні</option>
            </select>
            <span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text-faint);font-size:9px">▼</span>
        </label>

        <div style="flex:1"></div>

        <div style="display:flex;padding:3px;background:var(--surface);border:1px solid var(--border);border-radius:9px;gap:2px">
            <button type="button" title="Список" style="padding:6px 11px;background:var(--accent-dim);border:0;border-radius:7px;color:var(--accent);font:inherit;font-weight:600;font-size:12px;cursor:default">≣</button>
            <button type="button" title="Плитки" style="padding:6px 11px;background:transparent;border:0;border-radius:7px;color:var(--text-dim);font:inherit;font-size:12px;cursor:pointer;opacity:.55">▦</button>
            <button type="button" title="Групи" style="padding:6px 11px;background:transparent;border:0;border-radius:7px;color:var(--text-dim);font:inherit;font-size:12px;cursor:pointer;opacity:.55">⊟</button>
        </div>

        <span style="font-family:'IBM Plex Mono';font-size:12px;color:var(--text-faint)">{{ $c['filtered'] }} із {{ $c['total'] }}</span>
    </div>

    {{-- ── Таблиця сайтів (грід 26px 2.4fr 1fr 1fr 40px) ── --}}
    <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--surface)">
        <div style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:9px 14px;border-bottom:1px solid var(--border);background:var(--surface-2);font-size:10.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--text-faint);font-weight:600">
            <span></span><span>Сайт / домен</span><span>Групи</span><span>Статус</span><span></span>
        </div>

        @forelse($payload['sites'] as $site)
            <div>
                {{-- основний сайт --}}
                <a class="row-hover" href="/admin/sites/{{ $site['id'] }}/credentials"
                   style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:var(--row-pad);border-bottom:1px solid var(--border);cursor:pointer;color:inherit;text-decoration:none">
                    <button class="star" type="button" onclick="event.preventDefault();event.stopPropagation()">★</button>
                    <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                        <span style="font-weight:600;font-size:13px">{{ $site['name'] }}</span>
                        <span style="font-family:'IBM Plex Mono';font-size:11px;color:var(--text-dim)">{{ $site['domain'] }}</span>
                    </div>
                    <div style="display:flex;gap:4px;flex-wrap:wrap">
                        @foreach($site['groups'] as $g)
                            <span style="font-size:10.5px;padding:2px 7px;background:var(--surface-3);border-radius:5px;color:var(--text-dim)">{{ $g }}</span>
                        @endforeach
                    </div>
                    <div>
                        <span class="pill {{ $site['status'] }}"><span class="d"></span>{{ $labels[$site['status']] ?? $site['status'] }}</span>
                    </div>
                    <span style="color:var(--text-faint);text-align:right;font-size:15px">›</span>
                </a>

                {{-- піддомени --}}
                @foreach($site['subdomains'] as $sd)
                    <a class="row-hover" href="/admin/sites/{{ $sd['id'] }}/credentials"
                       style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:var(--row-pad);border-bottom:1px solid var(--border);cursor:pointer;background:var(--bg);color:inherit;text-decoration:none">
                        <span></span>
                        <div style="display:flex;align-items:center;gap:8px;min-width:0;padding-left:14px">
                            <span style="color:var(--text-faint);font-size:12px">└</span>
                            <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                <span style="font-size:12.5px;color:var(--text)">{{ $sd['name'] }}</span>
                                <span style="font-family:'IBM Plex Mono';font-size:10.5px;color:var(--text-faint)">{{ $sd['domain'] }}</span>
                            </div>
                        </div>
                        <span style="font-size:10.5px;color:var(--text-faint)">піддомен</span>
                        <div>
                            <span class="pill {{ $sd['status'] }}"><span class="d"></span>{{ $labels[$sd['status']] ?? $sd['status'] }}</span>
                        </div>
                        <span style="color:var(--text-faint);text-align:right;font-size:15px">›</span>
                    </a>
                @endforeach
            </div>
        @empty
            <div class="empty">
                <div class="box"></div>
                <div class="t">Нічого не знайдено</div>
                <div class="s">Змініть фільтри або пошуковий запит. Пошук охоплює також вкладені піддомени.</div>
                <a class="btn" href="/admin" style="margin-top:4px">Скинути фільтри</a>
            </div>
        @endforelse
    </div>
@endsection
