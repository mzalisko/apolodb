{{-- Керування секретом сайту (US4): відкликати / перевипустити. Секрет — показ ОДИН РАЗ.
     Самодостатній HTML, inline JS (без CDN, Принцип III). --}}
<!doctype html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DataBridge — Токен сайту</title>
    <style>
        :root { font-family: system-ui, sans-serif; }
        body { margin: 0; background: #f7f7f8; color: #1b1b1f; }
        header { padding: 16px 24px; border-bottom: 1px solid #e3e3e6; background: #fff; }
        main { padding: 24px; max-width: 640px; }
        .mono { font-family: ui-monospace, monospace; }
        button { padding: 9px 16px; border: 1px solid #d5d5d9; border-radius: 6px; cursor: pointer; margin-right: 8px; background: #fff; }
        button.primary { background: #f5a524; border: 0; font-weight: 600; }
        .secret { margin-top: 20px; padding: 14px; background: #fff8e6; border: 1px solid #f5a524; border-radius: 8px; display: none; }
        .secret code { font-family: ui-monospace, monospace; word-break: break-all; display: block; margin: 6px 0; }
        .msg { margin-top: 12px; color: #16a34a; }
        a { color: #444; }
    </style>
</head>
<body>
    <header><strong>DataBridge</strong> — Токен · <a href="/admin">← до списку</a></header>
    <main>
        <h2>{{ $site->name }} <span class="mono">({{ $site->domain }})</span></h2>
        <p>Публічний ідентифікатор: <code class="mono">{{ $site->site_identifier }}</code></p>
        <p>Секрет зберігається зашифровано й не показується повторно. За втрати — перевипустіть.</p>

        <button class="primary" id="reissue">Перевипустити секрет</button>
        <button id="revoke">Відкликати</button>

        <div class="msg" id="msg"></div>
        <div class="secret" id="secret">
            <strong>Новий секрет (показ ОДИН РАЗ):</strong>
            <code id="signingSecret"></code>
        </div>
    </main>
    <script>
        const siteUrl = '/admin/sites/{{ $site->id }}/credentials';
        async function post(path) {
            const res = await fetch(siteUrl + path, {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
            return { status: res.status, data: await res.json() };
        }
        document.getElementById('reissue').addEventListener('click', async () => {
            const { status, data } = await post('/reissue');
            if (status === 201) {
                document.getElementById('signingSecret').textContent = data.credentials.signing_secret;
                document.getElementById('secret').style.display = 'block';
                document.getElementById('msg').textContent = 'Секрет перевипущено. Старий недійсний.';
            }
        });
        document.getElementById('revoke').addEventListener('click', async () => {
            const { status } = await post('/revoke');
            if (status === 200) {
                document.getElementById('secret').style.display = 'none';
                document.getElementById('msg').textContent = 'Секрет відкликано.';
            }
        });
    </script>
</body>
</html>
