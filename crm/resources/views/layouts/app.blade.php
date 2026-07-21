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
                    <span style="background:var(--ok)"></span>
                    <span style="background:var(--reserve)"></span>
                    <span style="background:var(--text-faint)"></span>
                    <span style="background:var(--info)"></span>
                    <span style="background:var(--err)"></span>
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
            <a class="btn btn-accent" href="/admin/sites/create">+ Додати сайт</a>
        </header>

        <div class="content">
            @yield('content')
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
</script>
</body>
</html>
