@extends('layouts.app')
@section('title', 'DataBridge — Токен сайту')
@section('heading', 'Сайти')
@section('content')
    <div class="page-head"><h1>{{ $site->name }} <span class="mono" style="color: var(--muted); font-size: 15px;">{{ $site->domain }}</span></h1></div>

    <div class="card" style="padding: 24px; max-width: 560px;">
        <p style="margin-top: 0;">Публічний ідентифікатор:</p>
        <code class="mono" style="display: block; word-break: break-all;">{{ $site->site_identifier }}</code>
        <p style="color: var(--muted); font-size: 13px;">Секрет зберігається зашифровано й не показується повторно. За втрати — перевипустіть.</p>

        <div style="margin-top: 16px; display: flex; gap: 8px;">
            <button class="btn btn-primary" id="reissue" type="button">Перевипустити секрет</button>
            <button class="btn btn-danger" id="revoke" type="button">Відкликати</button>
            <a class="btn" href="/admin">← до списку</a>
        </div>

        <div class="msg" id="msg"></div>

        <div class="secret-box" id="secret" style="display: none;">
            <strong>Новий секрет — показ ОДИН РАЗ:</strong>
            <code id="signingSecret"></code>
        </div>
    </div>

    <script>
        const base = '/admin/sites/{{ $site->id }}/credentials';
        async function post(path) {
            const res = await fetch(base + path, {
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
@endsection
