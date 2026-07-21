@extends('layouts.app')
@section('title', 'DataBridge — Додати сайт')
@section('crumbs')<a href="/admin">Сайти</a><span class="sep">/</span><span class="cur">Додати сайт</span>@endsection
@section('content')
    <h1 class="page-h">Додати сайт</h1>

    <div class="card" style="padding: 22px; max-width: 520px;">
        <label for="name">Назва</label>
        <input id="name" type="text" placeholder="Мій сайт">

        <label for="domain">Домен</label>
        <input id="domain" type="text" placeholder="example.com">

        <div style="margin-top: 18px; display: flex; gap: 8px;">
            <button class="btn btn-accent" id="submit" type="button">Зареєструвати</button>
            <a class="btn" href="/admin">Скасувати</a>
        </div>

        <div class="err" id="err"></div>

        <div class="secret-box" id="secret" style="display: none;">
            <strong>Облікові дані — секрет показується ОДИН РАЗ, збережіть:</strong>
            <div class="k">site-id</div>
            <code id="siteId"></code>
            <div class="k">signing-secret</div>
            <code id="signingSecret"></code>
        </div>
    </div>

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
@endsection
