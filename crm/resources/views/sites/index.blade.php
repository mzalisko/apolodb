@extends('layouts.app')
@section('title', 'DataBridge — Сайти')
@section('crumbs')<span class="cur">Сайти</span>@endsection
@section('content')
    @php
        $c = $payload['counts'];
        $labels = ['online' => 'онлайн', 'pending' => 'очікує', 'offline' => 'офлайн', 'inactive' => 'неактивний'];
    @endphp

    <div class="filters">
        <div class="seg">
            <a href="#" class="disabled" style="pointer-events:none">★ Обране</a>
        </div>

        <label style="position:relative;display:flex">
            <select onchange="location.href=this.value"
                    style="padding:8px 30px 8px 12px;background:var(--surface);border:1px solid var(--border);border-radius:9px;color:var(--text);font:inherit;font-size:12.5px;cursor:pointer;min-width:150px;-webkit-appearance:none;appearance:none">
                <option value="/admin" @selected(! $filter)>Статус: усі</option>
                <option value="/admin?status=online" @selected($filter === 'online')>Онлайн</option>
                <option value="/admin?status=pending" @selected($filter === 'pending')>Очікує</option>
                <option value="/admin?status=offline" @selected($filter === 'offline')>Офлайн</option>
                <option value="/admin?status=inactive" @selected($filter === 'inactive')>Неактивні</option>
            </select>
            <span style="position:absolute;right:11px;top:50%;transform:translateY(-50%);pointer-events:none;color:var(--text-faint);font-size:9px">▼</span>
        </label>

        <div class="spacer"></div>

        <div class="seg">
            <a href="/admin" class="active" title="Список">≣</a>
            <a href="#" class="disabled" title="Плитки" style="pointer-events:none">▦</a>
            <a href="#" class="disabled" title="Групи" style="pointer-events:none">⊟</a>
        </div>
        <span class="count">{{ $c['filtered'] }} із {{ $c['total'] }}</span>
    </div>

    <div class="table">
        <div class="gr thead">
            <span></span><span>Сайт / домен</span><span>Групи</span><span>Статус</span><span></span>
        </div>

        @forelse($payload['sites'] as $site)
            <a class="gr trow" href="/admin/sites/{{ $site['id'] }}/credentials">
                <button class="star" type="button" onclick="event.preventDefault()">★</button>
                <div style="display:flex;flex-direction:column;gap:1px;min-width:0">
                    <span class="nm">{{ $site['name'] }}</span>
                    <span class="dm">{{ $site['domain'] }}</span>
                </div>
                <div style="display:flex;gap:4px;flex-wrap:wrap">
                    {{-- групи сайту (для цієї фічі зазвичай порожньо) --}}
                </div>
                <div>
                    <span class="pill {{ $site['status'] }}"><span class="d"></span>{{ $labels[$site['status']] ?? $site['status'] }}</span>
                </div>
                <span class="chev">›</span>
            </a>
        @empty
            <div class="empty">
                <div class="box"></div>
                <div class="t">Нічого не знайдено</div>
                <div class="s">Змініть фільтри або пошуковий запит. Пошук охоплює також вкладені піддомени.</div>
                <a class="btn" href="/admin" style="margin-top:4px">Скинути фільтри</a>
            </div>
        @endforelse
    </div>
@endsection
