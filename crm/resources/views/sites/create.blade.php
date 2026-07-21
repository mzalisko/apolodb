{{-- Форма реєстрації сайту (US1). Самодостатній HTML, inline JS (без CDN, Принцип III).
     POST на /admin/sites (JSON, CSRF-виняток); секрет показується ОДИН РАЗ. --}}
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DataBridge — Додати сайт</title>
    <style>
        :root { font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f7f7f8; color: #1b1b1f; }
        header { padding: 16px 24px; border-bottom: 1px solid #e3e3e6; background: #fff; }
        main { padding: 24px; max-width: 640px; }
        label { display: block; margin: 12px 0 4px; font-size: 13px; color: #555; }
        input { width: 100%; padding: 8px 10px; border: 1px solid #d5d5d9; border-radius: 6px; box-sizing: border-box; }
        button { margin-top: 16px; padding: 9px 16px; background: #f5a524; border: 0; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .secret { margin-top: 20px; padding: 14px; background: #fff8e6; border: 1px solid #f5a524; border-radius: 8px; display: none; }
        .secret code { font-family: ui-monospace, monospace; word-break: break-all; display: block; margin: 6px 0; }
        .err { color: #dc2626; margin-top: 10px; }
        a { color: #444; }
    </style>
</head>
<body>
    <header><strong>DataBridge</strong> — Додати сайт · <a href="/admin">← до списку</a></header>
    <main>
        <label for="name">Назва</label>
        <input id="name" type="text" placeholder="Мій сайт">
        <label for="domain">Домен</label>
        <input id="domain" type="text" placeholder="example.com">
        <button id="submit">Зареєструвати</button>
        <div class="err" id="err"></div>

        <div class="secret" id="secret">
            <strong>Облікові дані (секрет показується ОДИН РАЗ — збережіть його):</strong>
            <span>site-id:</span><code id="siteId"></code>
            <span>secret:</span><code id="signingSecret"></code>
        </div>
    </main>
    <script>
        document.getElementById('submit').addEventListener('click', async function () {
            document.getElementById('err').textContent = '';
            const res = await fetch('/admin/sites', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({
                    name: document.getElementById('name').value,
                    domain: document.getElementById('domain').value,
                }),
            });
            const data = await res.json();
            if (res.status === 201) {
                document.getElementById('siteId').textContent = data.credentials.site_id;
                document.getElementById('signingSecret').textContent = data.credentials.signing_secret;
                document.getElementById('secret').style.display = 'block';
            } else {
                document.getElementById('err').textContent = data.message || 'Помилка';
            }
        });
    </script>
</body>
</html>
