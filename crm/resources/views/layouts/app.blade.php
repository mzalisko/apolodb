@php
    $siteCount = \App\Models\Site::count();
@endphp
<!doctype html>
<html lang="uk" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DataBridge')</title>
    @vite('resources/css/app.css')
    <script>
        // Тема застосовується ДО першого паніту (без миготіння). За замовчуванням — темна.
        (function () {
            var s = localStorage.getItem('db-theme-v2');
            document.documentElement.setAttribute('data-theme', s === 'light' ? 'light' : 'dark');
        })();
    </script>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-logo">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#16181b" stroke-width="2.3" stroke-linecap="round"><ellipse cx="12" cy="6.2" rx="6.4" ry="2.7"></ellipse><path d="M5.6 6.2v5.2c0 1.5 2.9 2.7 6.4 2.7s6.4-1.2 6.4-2.7V6.2"></path><path d="M12 14.1v3.2"></path><path d="M7.4 13.4l-2.9 3.4"></path><path d="M16.6 13.4l2.9 3.4"></path><rect x="10.6" y="18.6" width="2.8" height="2.8" rx="0.7" fill="#16181b" stroke="none"></rect><rect x="2.5" y="18.1" width="2.8" height="2.8" rx="0.7" fill="#16181b" stroke="none"></rect><rect x="18.7" y="18.1" width="2.8" height="2.8" rx="0.7" fill="#16181b" stroke="none"></rect></svg>
            </div>
            <div>
                <div class="brand-name">Data<b>Bridge</b></div>
                <div class="brand-sub">панель керування</div>
            </div>
        </div>

        <nav class="nav">
            <a href="/admin" class="nav-btn {{ request()->is('admin*') ? 'active' : '' }}">
                <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="6" rx="1.5"/><rect x="3" y="14" width="18" height="6" rx="1.5"/></svg></span>
                <span class="lbl">Сайти</span>
                <span class="cnt">{{ $siteCount }}</span>
            </a>
            <a href="#" class="nav-btn disabled">
                <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5"/><path d="M3 8v10l9 5 9-5V8"/></svg></span>
                <span class="lbl">Масові зміни</span>
            </a>
            <a href="#" class="nav-btn disabled">
                <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l7 3v5c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
                <span class="lbl">Користувачі</span>
            </a>
            <a href="#" class="nav-btn disabled">
                <span class="ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3.5 2"/></svg></span>
                <span class="lbl">Журнал змін</span>
            </a>
        </nav>

        @php
            $me = auth()->user();
            $favSites = $me ? $me->favoriteSites()->whereNull('parent_site_id')->with('subdomains')->orderBy('name')->get() : collect();
            $favGroups = $me ? $me->favoriteGroups()->withCount('sites')->orderBy('name')->get() : collect();
        @endphp
        <div class="side-label">Обране</div>
        <div class="fav">
            @forelse($favSites as $fs)
                <div style="display:flex;flex-direction:column">
                    <a href="/admin/sites/{{ $fs->id }}/credentials"
                       style="display:flex;align-items:center;gap:8px;padding:6px 8px;border-radius:6px;color:var(--text-dim);font-size:12px;text-decoration:none">
                        <span style="width:5px;height:5px;border-radius:50%;background:var(--accent);flex-shrink:0"></span>
                        <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-family:var(--font-mono);font-size:11px">{{ $fs->domain }}</span>
                        @if($fs->subdomains->count())
                            <span style="font-family:var(--font-mono);font-size:10px;color:var(--text-faint)">+{{ $fs->subdomains->count() }}</span>
                        @endif
                    </a>
                    @foreach($fs->subdomains as $sub)
                        <a href="/admin/sites/{{ $sub->id }}/credentials"
                           style="display:flex;align-items:center;gap:7px;padding:4px 8px 4px 21px;border-radius:6px;color:var(--text-faint);font-size:11.5px;text-decoration:none">
                            <span style="font-size:10px">└</span><span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $sub->name }}</span>
                        </a>
                    @endforeach
                </div>
            @empty
            @endforelse

            @if($favGroups->count())
                <div style="padding:10px 8px 4px;font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:var(--text-faint)">Обрані групи</div>
                @foreach($favGroups as $fg)
                    <div style="display:flex;align-items:center;border-radius:6px">
                        <a href="/admin?group={{ urlencode($fg->name) }}"
                           style="flex:1;display:flex;align-items:center;gap:8px;padding:6px 4px 6px 8px;color:var(--text-dim);font-size:12px;min-width:0;text-decoration:none">
                            <span style="width:13px;height:13px;color:var(--accent);display:flex;flex-shrink:0"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><rect x="3" y="4" width="18" height="6" rx="1.5"/><rect x="3" y="14" width="18" height="6" rx="1.5"/></svg></span>
                            <span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $fg->name }}</span>
                            <span style="font-family:var(--font-mono);font-size:10px;padding:1px 6px;border-radius:5px;background:var(--surface-3);color:var(--text-faint);flex-shrink:0">{{ $fg->sites_count }} сайти</span>
                        </a>
                        <button type="button" title="Прибрати з обраного" onclick="dbToggleGroupFav({{ $fg->id }}, this)"
                                style="background:transparent;border:0;color:var(--accent);cursor:pointer;font-size:12px;padding:4px 8px;flex-shrink:0">★</button>
                    </div>
                @endforeach
            @endif

            @if($favSites->isEmpty() && $favGroups->isEmpty())
                <div class="fav-empty">Позначайте сайти ★ у списку, а групи — кнопкою «☆ Група» біля фільтра.</div>
            @endif
        </div>

        <div class="side-foot">
            <button class="legend-btn" type="button">
                <span>Легенда та позначення</span>
                <span class="legend-dots">
                    <span style="background:var(--ok)" title="Активний"></span>
                    <span style="background:var(--reserve)" title="Резерв"></span>
                    <span style="background:var(--text-faint)" title="Неактивний"></span>
                    <span style="background:var(--info)" title="Очікує"></span>
                    <span style="background:var(--err)" title="Помилка"></span>
                </span>
            </button>
            @auth
                <div class="user">
                    <div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div class="spacer" style="min-width:0">
                        <div class="nm">{{ auth()->user()->name }}</div>
                        <div class="rl">{{ auth()->user()->isAdmin() ? 'Адміністратор' : 'Менеджер' }}</div>
                    </div>
                    <form method="POST" action="/logout">@csrf
                        <button class="logout-btn" type="submit" title="Вийти">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round"><path d="M9 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h3"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
                        </button>
                    </form>
                </div>
            @endauth
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <div class="crumbs">@yield('crumbs')</div>
            <div class="spacer"></div>
            <div class="search">
                <span class="si"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg></span>
                <input type="text" placeholder="Пошук по сайтах і піддоменах…">
            </div>
            <button class="icon-btn" id="themeToggle" type="button" title="Перемкнути тему">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"><circle cx="12" cy="12" r="4.5"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
            </button>
            <button class="btn disabled" type="button">Масові зміни</button>
            <button class="btn" type="button" onclick="openGroupManager()">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="6" rx="1.5"/><rect x="3" y="14" width="18" height="6" rx="1.5"/></svg>Групи</button>
            <button class="btn btn-accent" type="button" onclick="openAddSite()">+ Додати сайт</button>
        </header>

        <div class="content">
            @yield('content')
        </div>
    </div>
</div>

{{-- ── Модалка «Додати сайт» (design/CRM v2.dc.html · рядок 1108) ── --}}
<div id="addSiteOverlay" onclick="if(event.target===this)closeAddSite()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:55;align-items:center;justify-content:center;padding:30px">
    <div style="width:460px;background:var(--surface);border:1px solid var(--border-strong);border-radius:14px;overflow:hidden;box-shadow:0 30px 70px rgba(0,0,0,.5)">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border)">
            <div style="font-weight:700;font-size:16px">Додати сайт</div>
            <div style="font-size:12.5px;color:var(--text-dim);margin-top:3px">Новий сайт у мережі</div>
        </div>

        {{-- крок 1: форма --}}
        <div id="addSiteStep1">
            <div style="padding:20px 22px;display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="display:block;font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);margin:0 0 7px">Назва</label>
                    <input id="addSiteName" type="text" placeholder="напр. Сервісний центр"
                           style="width:100%;padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font:inherit;font-size:13px;outline:none">
                </div>
                <div>
                    <label style="display:block;font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);margin:0 0 7px">Домен</label>
                    <input id="addSiteDomain" type="text" placeholder="novyi-sait.com.ua"
                           style="width:100%;padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:var(--font-mono);font-size:13px;outline:none">
                </div>
                <div id="addSiteError" style="display:none;color:var(--err);font-size:12.5px"></div>
            </div>
            <div style="padding:15px 22px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px">
                <div style="flex:1"></div>
                <button type="button" onclick="closeAddSite()" style="padding:9px 15px;background:transparent;border:1px solid var(--border);border-radius:8px;color:var(--text-dim);font:inherit;cursor:pointer;font-size:12.5px">Скасувати</button>
                <button type="button" id="addSiteSubmit" onclick="submitAddSite()" style="padding:9px 18px;background:var(--accent-btn);border:0;border-radius:8px;color:var(--on-accent);font:inherit;font-weight:600;cursor:pointer;font-size:12.5px">Додати сайт</button>
            </div>
        </div>

        {{-- крок 2: облікові дані (показ ОДИН РАЗ) --}}
        <div id="addSiteStep2" style="display:none">
            <div style="padding:20px 22px;display:flex;flex-direction:column;gap:12px">
                <div style="font-size:12.5px;color:var(--ok)">Сайт додано. Скопіюйте дані — секрет показується <b>один раз</b>.</div>
                <div>
                    <div style="font-size:10.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);margin-bottom:4px">site-id</div>
                    <code id="addSiteId" class="mono" style="display:block;word-break:break-all;padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;font-size:12.5px"></code>
                </div>
                <div>
                    <div style="font-size:10.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);margin-bottom:4px">signing-secret</div>
                    <code id="addSiteSecret" class="mono" style="display:block;word-break:break-all;padding:9px 12px;background:var(--accent-dim);border:1px solid var(--accent-btn);border-radius:8px;font-size:12.5px"></code>
                </div>
            </div>
            <div style="padding:15px 22px;border-top:1px solid var(--border);display:flex;gap:10px">
                <div style="flex:1"></div>
                <button type="button" onclick="location.href='/admin'" style="padding:9px 18px;background:var(--accent-btn);border:0;border-radius:8px;color:var(--on-accent);font:inherit;font-weight:600;cursor:pointer;font-size:12.5px">Готово</button>
            </div>
        </div>
    </div>
</div>

{{-- ── Модалка «Групи сайтів» (design/CRM v2.dc.html · рядок 1160) ── --}}
<div id="grpOverlay" onclick="if(event.target===this)closeGroupManager()"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:56;align-items:center;justify-content:center;padding:30px">
    <div style="width:520px;max-height:100%;overflow:auto;background:var(--surface);border:1px solid var(--border-strong);border-radius:14px;box-shadow:0 30px 70px rgba(0,0,0,.5)">
        <div style="padding:18px 22px;border-bottom:1px solid var(--border)">
            <div style="font-weight:700;font-size:16px">Групи сайтів</div>
            <div style="font-size:12.5px;color:var(--text-dim);margin-top:3px">Створіть групу або додайте наявні сайти до неї. Сайт може бути в кількох групах.</div>
        </div>
        <div style="padding:18px 22px;display:flex;flex-direction:column;gap:16px">
            <div>
                <label style="display:block;font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);margin:0 0 8px">Група</label>
                <div style="position:relative">
                    <div style="display:flex;gap:8px">
                        <input id="grpNewName" type="text" placeholder="Знайти або створити групу…" autocomplete="off"
                               oninput="grpFilter()" onfocus="grpFilter()"
                               onkeydown="if(event.key==='Enter'){event.preventDefault();grpCreate();}"
                               style="flex:1;padding:8px 11px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font:inherit;font-size:12.5px;outline:none">
                        <button type="button" onclick="grpCreate()" style="padding:8px 13px;background:var(--surface-2);border:1px solid var(--border-strong);border-radius:8px;color:var(--text);font:inherit;cursor:pointer;font-size:12px">Створити</button>
                    </div>
                    {{-- Випадний список: рендериться лише при пошуку/фокусі, скролиться, з лімітом → масштаб на 100+/1000+ груп --}}
                    <div id="grpDropdown" style="display:none;position:absolute;left:0;right:0;top:100%;margin-top:4px;max-height:210px;overflow:auto;background:var(--surface);border:1px solid var(--border-strong);border-radius:9px;z-index:6;box-shadow:0 14px 34px rgba(0,0,0,.45)"></div>
                </div>
            </div>
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px">
                    <div style="display:flex;align-items:center;gap:7px;min-width:0">
                        <label style="font-size:11px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint);margin:0;flex-shrink:0">Сайти в групі</label>
                        <span id="grpActiveName" style="background:var(--accent-dim);color:var(--accent);padding:2px 9px;border-radius:6px;font-size:11.5px;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">—</span>
                    </div>
                    <span style="font-family:var(--font-mono);font-size:11px;color:var(--text-faint);flex-shrink:0"><span id="grpMemberCount">0</span> обрано</span>
                </div>
                <input id="grpSearch" type="text" oninput="grpRenderSites()" placeholder="Пошук сайту…"
                       style="width:100%;padding:8px 11px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font:inherit;font-size:12.5px;outline:none;margin-bottom:8px">
                <div id="grpSiteList" style="max-height:200px;overflow:auto;border:1px solid var(--border);border-radius:9px;background:var(--bg)"></div>
            </div>
        </div>
        <div style="padding:15px 22px;border-top:1px solid var(--border);display:flex;align-items:center;gap:10px">
            <span style="font-size:12px;color:var(--text-dim)">Зміни застосовуються одразу</span>
            <div style="flex:1"></div>
            <button type="button" onclick="closeGroupManager()" style="padding:9px 18px;background:var(--accent-btn);border:0;border-radius:8px;color:var(--on-accent);font:inherit;font-weight:600;cursor:pointer;font-size:12.5px">Готово</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const root = document.documentElement;
        const btn = document.getElementById('themeToggle');
        if (btn) btn.addEventListener('click', function () {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('db-theme-v2', next);
        });
    })();

    // ── Модалка «Додати сайт» ──
    function openAddSite() {
        document.getElementById('addSiteStep1').style.display = 'block';
        document.getElementById('addSiteStep2').style.display = 'none';
        document.getElementById('addSiteError').style.display = 'none';
        document.getElementById('addSiteName').value = '';
        document.getElementById('addSiteDomain').value = '';
        document.getElementById('addSiteOverlay').style.display = 'flex';
        document.getElementById('addSiteName').focus();
    }
    function closeAddSite() {
        document.getElementById('addSiteOverlay').style.display = 'none';
    }
    function submitAddSite() {
        var name = document.getElementById('addSiteName').value.trim();
        var domain = document.getElementById('addSiteDomain').value.trim();
        var err = document.getElementById('addSiteError');
        var submit = document.getElementById('addSiteSubmit');
        err.style.display = 'none';
        submit.disabled = true;
        fetch('/admin/sites', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({ name: name, domain: domain })
        }).then(function (r) {
            return r.json().then(function (d) { return { status: r.status, d: d }; });
        }).then(function (res) {
            submit.disabled = false;
            if (res.status === 201) {
                document.getElementById('addSiteId').textContent = res.d.credentials.site_id;
                document.getElementById('addSiteSecret').textContent = res.d.credentials.signing_secret;
                document.getElementById('addSiteStep1').style.display = 'none';
                document.getElementById('addSiteStep2').style.display = 'block';
            } else {
                var msg = res.d.message || (res.d.errors ? Object.values(res.d.errors)[0][0] : 'Не вдалося додати сайт.');
                err.textContent = msg;
                err.style.display = 'block';
            }
        }).catch(function () {
            submit.disabled = false;
            err.textContent = 'Помилка мережі.';
            err.style.display = 'block';
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeAddSite();
    });

    // ── Обране (сайт / група) ──
    function dbToggleFav(id) {
        fetch('/admin/sites/' + id + '/favorite', { method: 'POST', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function () { location.reload(); });
    }
    function dbToggleGroupFav(id) {
        fetch('/admin/groups/' + id + '/favorite', { method: 'POST', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function () { location.reload(); });
    }

    // ── Менеджер груп ──
    var dbGrp = { groups: [], sites: [], active: null, changed: false };
    function openGroupManager() {
        document.getElementById('grpOverlay').style.display = 'flex';
        fetch('/admin/groups/data', { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function (d) {
                dbGrp.groups = d.groups; dbGrp.sites = d.sites;
                dbGrp.active = dbGrp.groups.length ? dbGrp.groups[0].id : null;
                document.getElementById('grpNewName').value = '';
                document.getElementById('grpDropdown').style.display = 'none';
                grpRenderActive(); grpRenderSites();
            });
    }
    function closeGroupManager() {
        document.getElementById('grpOverlay').style.display = 'none';
        if (dbGrp.changed) location.reload();   // оновити список/сайдбар під нові групи
    }
    // Активна група показується лише в заголовку «Сайти в групі «X»» — НЕ як чип/таб.
    function grpRenderActive() {
        var ag = dbGrp.groups.filter(function (g) { return g.id === dbGrp.active; })[0];
        document.getElementById('grpActiveName').textContent = ag ? ag.name : '—';
    }
    // Комбобокс: рендеримо лише збіги пошуку у випадному списку з лімітом → масштаб на будь-яку кількість груп.
    function grpFilter() {
        var dd = document.getElementById('grpDropdown'); dd.innerHTML = '';
        var raw = document.getElementById('grpNewName').value || '';
        var q = raw.toLowerCase();
        var CAP = 50;
        var matched = dbGrp.groups.filter(function (g) { return !q || g.name.toLowerCase().indexOf(q) !== -1; });
        matched.slice(0, CAP).forEach(function (g) {
            var active = g.id === dbGrp.active;
            var n = dbGrp.sites.filter(function (s) { return s.groups.indexOf(g.id) !== -1; }).length;
            var row = document.createElement('div');
            row.style.cssText = 'padding:8px 11px;cursor:pointer;font-size:12.5px;display:flex;align-items:center;justify-content:space-between;gap:10px;border-bottom:1px solid var(--border);'
                + (active ? 'background:var(--accent-dim);color:var(--accent)' : 'color:var(--text)');
            var nm = document.createElement('span'); nm.textContent = g.name;
            nm.style.cssText = 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
            var cnt = document.createElement('span'); cnt.textContent = n;
            cnt.style.cssText = 'font-family:var(--font-mono);font-size:10.5px;color:var(--text-faint);flex-shrink:0';
            row.appendChild(nm); row.appendChild(cnt);
            row.onmousedown = function (e) {
                e.preventDefault();
                dbGrp.active = g.id;
                document.getElementById('grpNewName').value = '';
                dd.style.display = 'none';
                grpRenderActive(); grpRenderSites();
            };
            dd.appendChild(row);
        });
        if (matched.length > CAP) {
            var more = document.createElement('div');
            more.textContent = '…ще ' + (matched.length - CAP) + ' — уточніть пошук';
            more.style.cssText = 'padding:8px 11px;font-size:11px;color:var(--text-faint)';
            dd.appendChild(more);
        }
        if (matched.length === 0) {
            var no = document.createElement('div');
            no.textContent = q ? 'Немає групи «' + raw.trim() + '» — натисніть «Створити».' : 'Груп ще немає.';
            no.style.cssText = 'padding:10px 11px;font-size:11.5px;color:var(--text-faint)';
            dd.appendChild(no);
        }
        dd.style.display = 'block';
    }
    function grpRenderSites() {
        var list = document.getElementById('grpSiteList'); list.innerHTML = '';
        var q = (document.getElementById('grpSearch').value || '').toLowerCase();
        var count = 0;
        dbGrp.sites.forEach(function (s) {
            var inGroup = dbGrp.active && s.groups.indexOf(dbGrp.active) !== -1;
            if (inGroup) count++;
            if (q && s.name.toLowerCase().indexOf(q) === -1 && s.domain.toLowerCase().indexOf(q) === -1) return;
            var label = document.createElement('label');
            label.style.cssText = 'display:flex;align-items:center;gap:10px;padding:7px 11px;border-bottom:1px solid var(--border);cursor:pointer;' + (inGroup ? 'background:var(--accent-dim)' : '');
            var cb = document.createElement('input'); cb.type = 'checkbox'; cb.checked = !!inGroup; cb.disabled = !dbGrp.active;
            cb.style.cssText = 'cursor:pointer;accent-color:var(--accent)';
            cb.onchange = function () { grpToggle(s, dbGrp.active); };
            var nm = document.createElement('span'); nm.textContent = s.name;
            nm.style.cssText = 'flex:1;font-size:12.5px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
            var dm = document.createElement('span'); dm.textContent = s.domain;
            dm.style.cssText = 'font-family:var(--font-mono);font-size:10.5px;color:var(--text-faint)';
            label.appendChild(cb); label.appendChild(nm); label.appendChild(dm);
            list.appendChild(label);
        });
        if (!list.children.length) { list.innerHTML = '<div style="padding:14px;font-size:11.5px;color:var(--text-faint);text-align:center">Нічого не знайдено.</div>'; }
        document.getElementById('grpMemberCount').textContent = count;
    }
    function grpToggle(s, groupId) {
        if (!groupId) return;
        fetch('/admin/sites/' + s.id + '/groups/' + groupId + '/toggle', { method: 'POST', headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function (res) {
                dbGrp.changed = true;
                var idx = s.groups.indexOf(groupId);
                if (res.member && idx === -1) s.groups.push(groupId);
                if (!res.member && idx !== -1) s.groups.splice(idx, 1);
                grpRenderSites();
            });
    }
    function grpCreate() {
        var input = document.getElementById('grpNewName');
        var name = input.value.trim();
        if (!name) return;
        fetch('/admin/groups', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ name: name }) })
            .then(function (r) { return r.json(); }).then(function (g) {
                dbGrp.changed = true; input.value = '';
                if (!dbGrp.groups.filter(function (x) { return x.id === g.id; }).length) dbGrp.groups.push(g);
                dbGrp.groups.sort(function (a, b) { return a.name.localeCompare(b.name, 'uk'); });
                dbGrp.active = g.id;
                document.getElementById('grpDropdown').style.display = 'none';
                grpRenderActive(); grpRenderSites();
            });
    }
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') closeGroupManager(); });
    // Ховаємо випадний список груп при кліку поза ним.
    document.addEventListener('click', function (e) {
        var dd = document.getElementById('grpDropdown');
        if (dd && dd.style.display === 'block' && e.target.id !== 'grpNewName' && !dd.contains(e.target)) dd.style.display = 'none';
    });
</script>
</body>
</html>
