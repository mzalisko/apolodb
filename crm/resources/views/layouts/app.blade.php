@php($siteCount = \App\Models\Site::count())
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

        <div class="side-label">Обране</div>
        <div class="fav">
            <div class="fav-empty">Позначайте сайти ★ у списку, а групи — кнопкою «☆ Група» біля фільтра.</div>
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
            <button class="btn disabled" type="button">Групи</button>
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
                           style="width:100%;padding:9px 12px;background:var(--bg);border:1px solid var(--border);border-radius:8px;color:var(--text);font-family:'IBM Plex Mono';font-size:13px;outline:none">
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
</script>
</body>
</html>
