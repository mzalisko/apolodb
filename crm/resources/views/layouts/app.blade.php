<!doctype html>
<html lang="uk" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'DataBridge')</title>
    @vite('resources/css/app.css')
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><span class="logo">DB</span> DataBridge</div>
        <nav class="nav">
            <a href="/admin" class="{{ request()->is('admin*') ? 'active' : '' }}"><span class="ic"></span> Сайти</a>
            <a href="#" class="disabled"><span class="ic"></span> Масові зміни</a>
            <a href="#" class="disabled"><span class="ic"></span> Користувачі</a>
            <a href="#" class="disabled"><span class="ic"></span> Журнал змін</a>
        </nav>
        <div class="sidebar-foot">
            @auth
                <div class="who">
                    <div class="avatar">{{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}</div>
                    <div>
                        <div class="name">{{ auth()->user()->name }}</div>
                        <div class="role">{{ auth()->user()->isAdmin() ? 'Адміністратор' : 'Менеджер' }}</div>
                    </div>
                </div>
                <form method="POST" action="/logout">@csrf<button class="logout" type="submit">Вихід</button></form>
            @endauth
        </div>
    </aside>
    <div class="main">
        <header class="topbar">
            <h2>@yield('heading', 'Сайти')</h2>
            <div class="spacer"></div>
            <button class="theme-toggle" id="themeToggle" title="Світла/темна тема" type="button">◐</button>
        </header>
        <main class="content">
            @yield('content')
        </main>
    </div>
</div>
<script>
    (function () {
        const root = document.documentElement;
        const saved = localStorage.getItem('db-theme');
        if (saved) root.setAttribute('data-theme', saved);
        const btn = document.getElementById('themeToggle');
        if (btn) btn.addEventListener('click', function () {
            const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-theme', next);
            localStorage.setItem('db-theme', next);
        });
    })();
</script>
</body>
</html>
