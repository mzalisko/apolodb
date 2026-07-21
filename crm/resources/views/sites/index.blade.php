@extends('layouts.app')
@section('title', 'DataBridge — Сайти')
@section('crumbs')<span class="cur">Сайти</span>@endsection
@section('content')
    @php
        $c = $payload['counts'];
        // Канонічний словник станів із дизайну (design.status): колір збігається зі статусом зв'язності.
        $labels = ['online' => 'Активний', 'pending' => 'Очікує', 'offline' => 'Помилка', 'inactive' => 'Неактивний'];
        $dot = ['online' => 'var(--ok)', 'pending' => 'var(--info)', 'offline' => 'var(--err)', 'inactive' => 'var(--text-faint)'];
        $sel = 'padding:8px 30px 8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:9px;color:var(--text);font:inherit;font-size:12.5px;cursor:pointer;min-width:120px;-webkit-appearance:none;appearance:none';
        $arrow = '<span style="position:absolute;right:10px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text-faint);font-size:9px">▼</span>';
        $favSet = array_flip($favoriteIds);
        $favGroupSet = array_flip($favoriteGroupIds);
        // Блоки для режиму «Групи»: групуємо (вже відфільтровані) сайти за назвою групи.
        $groupBlocks = [];
        foreach ($payload['sites'] as $s) {
            foreach ($s['groups'] as $gn) { $groupBlocks[$gn][] = $s; }
        }
        ksort($groupBlocks);

        $vt = fn ($active) => 'padding:6px 12px;border-radius:7px;border:0;cursor:pointer;font:inherit;font-size:12px;font-weight:500;'
            . ($active ? 'background:var(--accent-dim);color:var(--accent)' : 'background:transparent;color:var(--text-dim)');
    @endphp

    {{-- ── Панель фільтрів ── --}}
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
        <div style="display:flex;align-items:center;gap:8px;padding:3px;background:var(--surface);border:1px solid var(--border);border-radius:9px">
            <button type="button" title="Показати лише обране" onclick="dbFilter('fav', {{ $favOnly ? "''" : "'1'" }})"
                    style="padding:5px 11px;border-radius:7px;border:0;cursor:pointer;font:inherit;font-size:12.5px;font-weight:500;{{ $favOnly ? 'background:var(--accent-dim);color:var(--accent)' : 'background:transparent;color:var(--text-dim)' }}">★ Обране</button>
        </div>

        @if($group && $groupId)
            <button type="button" title="{{ $favoriteGroup ? 'Прибрати групу з обраного' : 'Додати групу в обране' }}"
                    onclick="dbToggleGroupFav({{ $groupId }})"
                    style="padding:8px 12px;border-radius:9px;font:inherit;font-size:12.5px;cursor:pointer;border:1px solid {{ $favoriteGroup ? 'var(--accent)' : 'var(--border)' }};background:{{ $favoriteGroup ? 'var(--accent-dim)' : 'var(--surface)' }};color:{{ $favoriteGroup ? 'var(--accent)' : 'var(--text-dim)' }}">{{ $favoriteGroup ? '★' : '☆' }} Група</button>
        @endif

        <label style="position:relative;display:flex;margin:0">
            <select onchange="dbFilter('group', this.value)" style="{{ $sel }}">
                <option value="" @selected(! $group)>Усі групи</option>
                @foreach($groups as $g)
                    <option value="{{ $g }}" @selected($group === $g)>{{ $g }}</option>
                @endforeach
            </select>{!! $arrow !!}
        </label>

        <label style="position:relative;display:flex;margin:0">
            <select onchange="dbFilter('status', this.value)" style="{{ $sel }}">
                <option value="" @selected(! $filter)>Будь-який статус</option>
                <option value="online" @selected($filter === 'online')>Активний</option>
                <option value="pending" @selected($filter === 'pending')>Очікує</option>
                <option value="offline" @selected($filter === 'offline')>Помилка</option>
                <option value="inactive" @selected($filter === 'inactive')>Неактивний</option>
            </select>{!! $arrow !!}
        </label>

        <label style="position:relative;display:flex;margin:0">
            <select style="{{ $sel }}" title="Фільтр за типом даних (наступні фічі)">
                <option>Усі типи даних</option>
                <option>Телефони</option><option>Месенджери</option><option>Ціни</option><option>Соцмережі</option><option>Адреси</option>
            </select>{!! $arrow !!}
        </label>

        <label style="position:relative;display:flex;margin:0">
            <select style="{{ $sel }}" title="Фільтр за гео-правилом (наступні фічі)">
                <option>Будь-яке гео</option>
                <option>Показ всім</option><option>Всім, крім…</option><option>Тільки…</option>
            </select>{!! $arrow !!}
        </label>

        <div style="flex:1"></div>

        <div style="display:flex;padding:3px;background:var(--surface);border:1px solid var(--border);border-radius:9px;gap:2px">
            <button type="button" title="Таблиця з піддоменами" onclick="dbFilter('view','list')" style="{{ $vt($view === 'list') }}">Список</button>
            <button type="button" title="Картки сайтів" onclick="dbFilter('view','tiles')" style="{{ $vt($view === 'tiles') }}">Плитки</button>
            <button type="button" title="Групувати по групах" onclick="dbFilter('view','groups')" style="{{ $vt($view === 'groups') }}">Групи</button>
        </div>

        <span style="font-family:var(--font-mono);font-size:12px;color:var(--text-faint)">{{ $c['filtered'] }} із {{ $c['total'] }} сайтів</span>
    </div>

    {{-- ═══ Режим ПЛИТКИ ═══ --}}
    @if($view === 'tiles')
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:12px">
            @forelse($payload['sites'] as $s)
                @php $isFav = isset($favSet[$s['id']]); $subs = $s['subdomains']; @endphp
                <a class="card-hover" href="/admin/sites/{{ $s['id'] }}/credentials"
                   style="border:1px solid var(--border);border-radius:12px;background:var(--surface);padding:14px 15px;cursor:pointer;display:flex;flex-direction:column;gap:11px;color:inherit;text-decoration:none">
                    <div style="display:flex;align-items:flex-start;gap:9px">
                        <div style="flex:1;min-width:0">
                            <div style="font-weight:600;font-size:13.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s['name'] }}</div>
                            <div style="font-family:var(--font-mono);font-size:11px;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s['domain'] }}</div>
                        </div>
                        <button class="star" type="button" style="color:{{ $isFav ? 'var(--accent)' : 'var(--text-faint)' }};font-size:15px;flex-shrink:0"
                                onclick="event.preventDefault();event.stopPropagation();dbToggleFav({{ $s['id'] }})">★</button>
                    </div>
                    <div style="display:flex;align-items:center;gap:7px;flex-wrap:wrap">
                        <span class="pill {{ $s['status'] }}"><span class="d"></span>{{ $labels[$s['status']] ?? $s['status'] }}</span>
                        @if(count($subs))<span style="font-size:11px;color:var(--text-faint)">{{ count($subs) }} піддом.</span>@endif
                    </div>
                    @if(count($s['groups']))
                        <div style="display:flex;gap:5px;flex-wrap:wrap">
                            @foreach($s['groups'] as $gn)<span style="font-size:10.5px;padding:2px 8px;background:var(--surface-3);border-radius:5px;color:var(--text-dim)">{{ $gn }}</span>@endforeach
                        </div>
                    @endif
                    @if(count($subs))
                        <div style="display:flex;flex-direction:column;gap:3px;padding-top:9px;border-top:1px solid var(--border)">
                            @foreach($subs as $sd)
                                <div style="display:flex;align-items:center;gap:7px">
                                    <span style="color:var(--text-faint);font-size:11px">└</span>
                                    <span style="flex:1;font-size:11.5px;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $sd['name'] }}</span>
                                    <span style="width:5px;height:5px;border-radius:50%;background:{{ $dot[$sd['status']] ?? 'var(--text-faint)' }};flex-shrink:0"></span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <div style="display:flex;align-items:center;gap:8px;padding-top:10px;border-top:1px solid var(--border)">
                        <div style="flex:1"></div>
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $dot[$s['status']] ?? 'var(--text-faint)' }}"></span>
                        <span style="font-family:var(--font-mono);font-size:10.5px;color:var(--text-dim)">
                            @php
                                $ls = $s['last_seen_at'] ? \Illuminate\Support\Carbon::parse($s['last_seen_at']) : null;
                                $lsText = 'ще не звітував';
                                if ($ls) {
                                    $sec = $ls->diffInSeconds();
                                    $lsText = $sec < 60 ? 'щойно'
                                        : ($sec < 3600 ? intdiv($sec, 60).' хв тому'
                                        : ($sec < 86400 ? intdiv($sec, 3600).' год тому'
                                        : intdiv($sec, 86400).' дн тому'));
                                }
                            @endphp
                            {{ $lsText }}
                        </span>
                    </div>
                </a>
            @empty
                <div class="empty" style="grid-column:1/-1"><div class="box"></div><div class="t">Нічого не знайдено</div><div class="s">Змініть фільтри або пошуковий запит.</div><a class="btn" href="/admin" style="margin-top:4px">Скинути фільтри</a></div>
            @endforelse
        </div>

    {{-- ═══ Режим ГРУПИ ═══ --}}
    @elseif($view === 'groups')
        <div style="display:flex;flex-direction:column;gap:10px">
            @forelse($groupBlocks as $gname => $gsites)
                <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--surface)">
                    @php $gid = $groupIdByName[$gname] ?? null; $gfav = $gid && isset($favGroupSet[$gid]); @endphp
                    <div class="grp-head" onclick="dbToggleGroup(this)" style="display:flex;align-items:center;gap:11px;padding:12px 15px;background:var(--surface-2);cursor:pointer">
                        <span style="width:18px;height:18px;color:var(--accent);display:flex"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="6" rx="1.5"/><rect x="3" y="14" width="18" height="6" rx="1.5"/></svg></span>
                        <span style="font-weight:600;font-size:14px;flex:1">{{ $gname }}</span>
                        <span style="font-family:var(--font-mono);font-size:11px;padding:2px 8px;border-radius:6px;background:var(--surface-3);color:var(--text-dim)">{{ count($gsites) }} сайтів</span>
                        @if($gid)
                            <button type="button" title="{{ $gfav ? 'Прибрати групу з обраного' : 'Додати групу в обране' }}" onclick="event.stopPropagation();dbToggleGroupFav({{ $gid }})" style="background:transparent;border:0;cursor:pointer;color:{{ $gfav ? 'var(--accent)' : 'var(--text-faint)' }};font-size:14px;padding:0 2px">★</button>
                        @endif
                        <span class="grp-chev" style="color:var(--text-faint);font-size:12px;width:14px;text-align:center;transition:transform .15s">▾</span>
                    </div>
                    <div class="grp-body">
                    @foreach($gsites as $s)
                        @php $isFav = isset($favSet[$s['id']]); @endphp
                        <a class="row-hover" href="/admin/sites/{{ $s['id'] }}/credentials"
                           style="display:grid;grid-template-columns:2.4fr 1fr 30px;align-items:center;padding:8px 15px;border-top:1px solid var(--border);cursor:pointer;color:inherit;text-decoration:none">
                            <div style="display:flex;align-items:center;gap:9px;min-width:0">
                                <button class="star" type="button" style="color:{{ $isFav ? 'var(--accent)' : 'var(--text-faint)' }};font-size:13px;flex-shrink:0"
                                        onclick="event.preventDefault();event.stopPropagation();dbToggleFav({{ $s['id'] }})">★</button>
                                <div style="min-width:0">
                                    <div style="font-weight:600;font-size:12.5px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s['name'] }}</div>
                                    <div style="font-family:var(--font-mono);font-size:10.5px;color:var(--text-dim);overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $s['domain'] }}</div>
                                </div>
                            </div>
                            <div><span class="pill {{ $s['status'] }}"><span class="d"></span>{{ $labels[$s['status']] ?? $s['status'] }}</span></div>
                            <span style="color:var(--text-faint);text-align:right;font-size:14px">›</span>
                        </a>
                    @endforeach
                    </div>
                </div>
            @empty
                <div class="empty"><div class="box"></div><div class="t">Немає груп для показу</div><div class="s">Жоден сайт у поточному фільтрі не входить у групу.</div><a class="btn" href="/admin" style="margin-top:4px">Скинути фільтри</a></div>
            @endforelse
        </div>

    {{-- ═══ Режим СПИСОК (за замовчуванням) ═══ --}}
    @else
        <div style="border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--surface)">
            <div style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:9px 14px;border-bottom:1px solid var(--border);background:var(--surface-2);font-size:10.5px;letter-spacing:.05em;text-transform:uppercase;color:var(--text-faint);font-weight:600">
                <span></span><span>Сайт / домен</span><span>Групи</span><span>Статус</span><span></span>
            </div>

            @forelse($payload['sites'] as $site)
                @php $isFav = isset($favSet[$site['id']]); @endphp
                <div>
                    <a class="row-hover" href="/admin/sites/{{ $site['id'] }}/credentials"
                       style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:var(--row-pad);border-bottom:1px solid var(--border);cursor:pointer;color:inherit;text-decoration:none">
                        <button class="star" type="button" title="{{ $isFav ? 'Прибрати з обраного' : 'Додати в обране' }}"
                                style="color:{{ $isFav ? 'var(--accent)' : 'var(--text-faint)' }}"
                                onclick="event.preventDefault();event.stopPropagation();dbToggleFav({{ $site['id'] }})">★</button>
                        <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                            <span style="font-weight:600;font-size:13px">{{ $site['name'] }}</span>
                            <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-dim)">{{ $site['domain'] }}</span>
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

                    @foreach($site['subdomains'] as $sd)
                        <a class="row-hover" href="/admin/sites/{{ $sd['id'] }}/credentials"
                           style="display:grid;grid-template-columns:26px 2.4fr 1fr 1fr 40px;align-items:center;padding:var(--row-pad);border-bottom:1px solid var(--border);cursor:pointer;background:var(--bg);color:inherit;text-decoration:none">
                            <span></span>
                            <div style="display:flex;align-items:center;gap:8px;min-width:0;padding-left:14px">
                                <span style="color:var(--text-faint);font-size:12px">└</span>
                                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                                    <span style="font-size:12.5px;color:var(--text)">{{ $sd['name'] }}</span>
                                    <span style="font-family:var(--font-mono);font-size:10.5px;color:var(--text-faint)">{{ $sd['domain'] }}</span>
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
    @endif

    <script>
        // Фільтр/вкладки зберігають інші query-параметри (комбіновані фільтри — design brief §2).
        function dbFilter(key, val) {
            var u = new URL(location.href);
            if (val) { u.searchParams.set(key, val); } else { u.searchParams.delete(key); }
            location.href = u.pathname + u.search;
        }
        // Згортання блоку групи (design: chevron ▾/▸).
        function dbToggleGroup(head) {
            var body = head.nextElementSibling;
            var chev = head.querySelector('.grp-chev');
            var collapsed = body.style.display === 'none';
            body.style.display = collapsed ? '' : 'none';
            if (chev) chev.textContent = collapsed ? '▾' : '▸';
        }
    </script>
@endsection
