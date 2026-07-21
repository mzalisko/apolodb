@extends('layouts.app')
@section('title', 'DataBridge — Токен сайту')
@section('crumbs')<a href="/admin">Сайти</a><span class="sep">/</span><span class="cur">{{ $site->name }}</span>@endsection
@section('content')
    <h1 class="page-h">{{ $site->name }} <span class="mono" style="color:var(--text-dim);font-size:14px;font-weight:400">{{ $site->domain }}</span></h1>

    <div class="card" style="padding: 22px; max-width: 560px;">
        <div class="k" style="font-size:10.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--text-faint)">Публічний ідентифікатор</div>
        <code class="mono" style="display:block;word-break:break-all;margin:5px 0 14px;font-size:12.5px">{{ $site->site_identifier }}</code>
        <div style="color:var(--text-dim);font-size:12px">Секрет зберігається зашифровано й не показується повторно. За втрати — перевипустіть.</div>

        <div style="margin-top: 16px; display: flex; gap: 8px;">
            <button class="btn btn-accent" id="reissue" type="button">Перевипустити секрет</button>
            <button class="btn btn-danger" id="revoke" type="button">Відкликати</button>
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
