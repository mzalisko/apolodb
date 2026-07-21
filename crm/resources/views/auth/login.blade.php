<!doctype html>
<html lang="uk" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DataBridge — Вхід</title>
    @vite('resources/css/app.css')
    <style>
        body { display: grid; place-items: center; min-height: 100vh; background: var(--sidebar-bg); }
        .login { background: var(--surface); padding: 32px; border-radius: 14px; width: 330px; box-shadow: 0 20px 60px rgba(0,0,0,.4); }
        .login .logo { width: 36px; height: 36px; background: var(--accent-btn); border-radius: 9px; display: grid; place-items: center; color: #1b1b1f; font-weight: 800; margin-bottom: 14px; }
        .login h1 { margin: 0; font-size: 20px; }
        .login .sub { color: var(--muted); font-size: 13px; margin-bottom: 4px; }
        .login button { margin-top: 22px; width: 100%; justify-content: center; }
        .hint { color: var(--muted); font-size: 12px; text-align: center; margin-top: 16px; }
    </style>
</head>
<body>
    <form class="login" method="POST" action="/login">
        @csrf
        <div class="logo">DB</div>
        <h1>DataBridge</h1>
        <div class="sub">Панель керування мережею</div>

        <label for="email">Робоча пошта</label>
        <input id="email" name="email" type="email" value="{{ old('email') }}" autofocus required>

        <label for="password">Пароль</label>
        <input id="password" name="password" type="password" required>

        @error('email')<div class="err">{{ $message }}</div>@enderror

        <button class="btn btn-primary" type="submit">Увійти</button>
        <div class="hint">Демо: admin@databridge.local / password</div>
    </form>
</body>
</html>
