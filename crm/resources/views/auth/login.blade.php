<!doctype html>
<html lang="uk" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DataBridge — Вхід</title>
    @vite('resources/css/app.css')
    <style>
        body { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; background: var(--bg); }
        .gate { width: 380px; }
        .gate .head { display: flex; align-items: center; gap: 11px; margin-bottom: 26px; justify-content: center; }
        .gate .logo { width: 36px; height: 36px; border-radius: 9px; background: var(--accent-btn); display: flex; align-items: center; justify-content: center; }
        .gate .nm { font-weight: 700; font-size: 17px; }
        .gate .nm b { color: var(--accent); }
        .gate .sub { font-size: 10.5px; color: var(--text-faint); letter-spacing: .04em; text-transform: uppercase; }
        .gate .card { background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 26px; }
        .gate h1 { font-weight: 700; font-size: 16px; margin: 0 0 3px; }
        .gate .lead { font-size: 12.5px; color: var(--text-dim); margin-bottom: 20px; }
        .gate label { display: block; font-size: 11px; letter-spacing: .04em; text-transform: uppercase; color: var(--text-faint); margin: 0 0 7px; }
        .gate input[type=email], .gate input[type=password] { width: 100%; padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 8px; color: var(--text); font-family: var(--font-mono); font-size: 13px; outline: none; margin-bottom: 14px; }
        .gate input:focus { border-color: var(--accent); }
        .roles { display: flex; gap: 8px; margin-bottom: 20px; }
        .roles button { flex: 1; padding: 10px; background: var(--surface); border: 1px solid var(--border); border-radius: 9px; cursor: pointer; font: inherit; font-size: 12.5px; color: var(--text-dim); font-weight: 500; }
        .roles button.active { background: var(--accent-dim); border-color: var(--accent); color: var(--accent); }
        .gate .submit { width: 100%; padding: 11px; background: var(--accent-btn); border: 0; border-radius: 9px; color: var(--on-accent); font-weight: 600; font-size: 13.5px; cursor: pointer; }
        .gate .foot { text-align: center; font-size: 11px; color: var(--text-faint); margin-top: 14px; }
        .gate .err { color: var(--err); font-size: 12.5px; margin: -6px 0 14px; }
    </style>
</head>
<body>
    <form class="gate" method="POST" action="/login">
        @csrf
        <div class="head">
            <div class="logo">
                <svg width="23" height="23" viewBox="0 0 24 24" fill="none" stroke="#16181b" stroke-width="2.1" stroke-linecap="round"><ellipse cx="12" cy="6.2" rx="6.4" ry="2.7"></ellipse><path d="M5.6 6.2v5.2c0 1.5 2.9 2.7 6.4 2.7s6.4-1.2 6.4-2.7V6.2"></path><path d="M12 14.1v3.2"></path><path d="M7.4 13.4l-2.9 3.4"></path><path d="M16.6 13.4l2.9 3.4"></path><rect x="10.6" y="18.6" width="2.8" height="2.8" rx="0.7" fill="#16181b" stroke="none"></rect><rect x="2.5" y="18.1" width="2.8" height="2.8" rx="0.7" fill="#16181b" stroke="none"></rect><rect x="18.7" y="18.1" width="2.8" height="2.8" rx="0.7" fill="#16181b" stroke="none"></rect></svg>
            </div>
            <div style="line-height:1.1">
                <div class="nm">Data<b>Bridge</b></div>
                <div class="sub">панель керування</div>
            </div>
        </div>

        <div class="card">
            <h1>Вхід до системи</h1>
            <div class="lead">Внутрішній інструмент. Доступ лише для менеджерів і адміністраторів.</div>

            <label for="email">Робоча пошта</label>
            <input id="email" name="email" type="email" value="{{ old('email', 'admin@databridge.local') }}" autofocus required>

            <label for="password">Пароль</label>
            <input id="password" name="password" type="password" value="password" required>

            @error('email')<div class="err">{{ $message }}</div>@enderror

            <label>Роль сесії</label>
            <div class="roles">
                <button type="button" class="active" data-role>Адміністратор</button>
                <button type="button" data-role>Менеджер</button>
            </div>

            <button class="submit" type="submit">Увійти</button>
        </div>

        <div class="foot">Сесія активна 8 годин · вихід автоматично після завершення</div>
    </form>

    <script>
        document.querySelectorAll('[data-role]').forEach(function (b) {
            b.addEventListener('click', function () {
                document.querySelectorAll('[data-role]').forEach(x => x.classList.remove('active'));
                b.classList.add('active');
            });
        });
    </script>
</body>
</html>
