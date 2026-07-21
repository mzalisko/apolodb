@extends('layouts.app')
@section('title', 'DataBridge — Сайти')
@section('crumbs')<span class="cur">Сайти</span>@endsection
@section('content')
    @php
        $c = $payload['counts'];
        // Канонічний словник станів із дизайну (design.status): колір збігається зі статусом зв'язності.
        $labels = ['online' => 'Активний', 'pending' => 'Очікує', 'offline' => 'Помилка', 'inactive' => 'Неактивний'];
        $sel = 'padding:8px 30px 8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:9px;color:var(--text);font:inherit;font-size:12.5px;cursor:pointer;min-width:120px;-webkit-appearance:none;appearance:none';
        $arrow = '<span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text-faint);font-size:9px">▼</span>';
    @endphp

    {{-- ── Панель фільтрів (точно як design/CRM v2.dc.html · рядок 153) ── --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px;padding:3px;background:var(--surface);border:1px solid var(--border);border-radius:9px">
            <button type="button" title="Показати лише обране"
                    style="padding:5px 11px;border-radius:7px;border:0;cursor:pointer;font:inherit;font-size:12.5px;font-weight:500;background:transparent;color:var(--text-dim)">★ Обране</button>
        </div>

        @if($group && $groupId)
            <button type="button" title="{{ $favoriteGroup ? 'Прибрати групу з обраного' : 'Додати групу в обране' }}"
                    onclick="dbToggleGroupFav({{ $groupId }})"
                    style="padding:8px 12px;border-radius:9px;font:inherit;font-size:12.5px;cursor:pointer;border:1px solid {{ $favoriteGroup ? 'var(--accent)' : 'var(--border)' }};background:{{ $favoriteGroup ? 'var(--accent-dim)' : 'var(--surface)' }};color:{{ $favoriteGroup ? 'var(--accent)' : 'var(--text-dim)' }}">{{ $favoriteGroup ? '★' : '☆' }} Група</button>
        @endif

        {{-- Групи --}}
        <label style="position:relative;display:flex;margin:0">
            <select onchange="dbFilter('group', this.value)" style="{{ $sel }}">
                <option value="" @selected(! $group)>Усі групи</option>
                @foreach($groups as $g)
                    <option value="{{ $g }}" @selected($group === $g)>{{ $g }}</option>
                @endforeach
            </select>{!! $arrow !!}
        </label>

        {{-- Статус --}}
        <label style="position:relative;display:flex;margin:0">
            <select onchange="dbFilter('status', this.value)" style="{{ $sel }}">
                <option value="" @selected(! $filter)>Будь-який статус</option>
                <option value="online" @selected($filter === 'online')>Активний</option>
                <option value="pending" @selected($filter === 'pending')>Очікує</option>
                <option value="offline" @selected($filter === 'offline')>Помилка</option>
                <option value="inactive" @selected($filter === 'inactive')>Неактивний</option>
            </select>{!! $arrow !!}
        </label>

        {{-- Типи даних (телефони/ціни/… — наступні фічі) --}}
        <label style="position:relative;display:flex;margin:0">
            <select style="{{ $sel }}" title="Фільтр за типом даних (наступні фічі)">
                <option>Усі типи даних</option>
                <option>Телефони</option><option>Месенджери</option><option>Ціни</option><option>Соцмережі</option><option>Адреси</option>
            </select>{!! $arrow !!}
        </label>

        {{-- Гео (наступні фічі) --}}
        <label style="position:relative;display:flex;margin:0">
            <select style="{{ $sel }}" title="Фільтр за гео-правилом (наступні фічі)">
                <option>Будь-яке гео</option>
                <option>Показ всім</option><option>Всім, крім…</option><option>Тільки…</option>
            </select>{!! $arrow !!}
        </label>

        <div style="flex:1"></div>

        {{-- View-tabs --}}
        <div style="display:flex;padding:3px;background:var(--surface);border:1px solid var(--border);border-radius:9px;gap:2px">
            <button type="button" title="Таблиця з піддоменами" style="padding:6px 12px;border-radius:7px;border:0;cursor:default;font:inherit;font-size:12px;font-weight:500;background:var(--accent-dim);color:var(--accent)">Список</button>
            <button type="button" title="Картки сайтів (наступні фічі)" style="padding:6px 12px;border-radius:7px;border:0;cursor:default;font:inherit;font-size:12px;font-weight:500;background:transparent;color:var(--text-dim)">Плитки</button>
            <button type="button" title="Групувати по групах (наступні фічі)" style="padding:6px 12px;border-radius:7px;border:0;cursor:default;font:inherit;font-size:12px;font-weight:500;background:transparent;color:var(--text-dim)">Групи</button>
        </div>

        <span style="font-family:'IBM Plex Mono';font-size:12px;color:var(--text-faint)">{{ $c['filtered'] }} із {{ $c['total'] }} сайтів</span>
    </div>

    {{-- ── Таблиця сайтів (грід 26px 2.4fr 1fr 1fr 40px) ── --}}
    <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--surface)">
        <div style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:9px 14px;border-bottom:1px solid var(--border);background:var(--surface-2);font-size:10.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--text-faint);font-weight:600">
            <span></span><span>Сайт / домен</span><span>Групи</span><span>Статус</span><span></span>
        </div>

        @forelse($payload['sites'] as $site)
            <div>
                {{-- основний сайт --}}
                @php $isFav = in_array($site['id'], $favoriteIds); @endphp
                <a class="row-hover" href="/admin/sites/{{ $site['id'] }}/credentials"
                   style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:var(--row-pad);border-bottom:1px solid var(--border);cursor:pointer;color:inherit;text-decoration:none">
                    <button class="star" type="button" title="{{ $isFav ? 'Прибрати з обраного' : 'Додати в обране' }}"
                            style="color:{{ $isFav ? 'var(--accent)' : 'var(--text-faint)' }}"
                            onclick="event.preventDefault();event.stopPropagation();dbToggleFav({{ $site['id'] }})">★</button>
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

    <script>
        // Фільтр зберігає інші query-параметри (комбіновані фільтри — design brief §2).
        function dbFilter(key, val) {
            var u = new URL(location.href);
            if (val) { u.searchParams.set(key, val); } else { u.searchParams.delete(key); }
            location.href = u.pathname + u.search;
        }
    </script>
@endsection
