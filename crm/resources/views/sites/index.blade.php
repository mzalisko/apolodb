@extends('layouts.app')
@section('title', 'DataBridge — Сайти')
@section('heading', 'Сайти')
@section('content')
    @php
        $c = $payload['counts'];
        $labels = ['online' => 'онлайн', 'pending' => 'очікує', 'offline' => 'офлайн', 'inactive' => 'неактивний'];
    @endphp

    <div class="page-head">
        <h1>Сайти мережі</h1>
        <div class="spacer"></div>
        <a href="/admin/sites/create" class="btn btn-primary">+ Додати сайт</a>
    </div>

    <div class="filters">
        <a href="/admin" class="chip {{ $filter ? '' : 'active' }}">Усі</a>
        <a href="/admin?status=online" class="chip {{ $filter === 'online' ? 'active' : '' }}">Онлайн · {{ $c['by_status']['online'] }}</a>
        <a href="/admin?status=pending" class="chip {{ $filter === 'pending' ? 'active' : '' }}">Очікує · {{ $c['by_status']['pending'] }}</a>
        <a href="/admin?status=offline" class="chip {{ $filter === 'offline' ? 'active' : '' }}">Офлайн · {{ $c['by_status']['offline'] }}</a>
        <a href="/admin?status=inactive" class="chip {{ $filter === 'inactive' ? 'active' : '' }}">Неактивні · {{ $c['by_status']['inactive'] }}</a>
        <span class="counter">{{ $c['filtered'] }} із {{ $c['total'] }}</span>
    </div>

    <div class="card">
        <table>
            <thead>
                <tr><th>Сайт</th><th>Домен</th><th>Статус</th><th>Останнє оновлення</th><th></th></tr>
            </thead>
            <tbody>
                @forelse($payload['sites'] as $site)
                    <tr>
                        <td>{{ $site['name'] }}</td>
                        <td class="mono">{{ $site['domain'] }}</td>
                        <td>
                            <span class="status">
                                <span class="dot s-{{ $site['status'] }}"></span>{{ $labels[$site['status']] ?? $site['status'] }}
                            </span>
                        </td>
                        <td class="mono">{{ $site['last_seen_at'] ? \Illuminate\Support\Carbon::parse($site['last_seen_at'])->diffForHumans() : '—' }}</td>
                        <td class="row-actions"><a class="btn" href="/admin/sites/{{ $site['id'] }}/credentials">Токен</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">Сайтів не знайдено. <a href="/admin">Скинути фільтри</a></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="legend">
        <span><span class="dot s-online"></span> активний</span>
        <span><span class="dot s-pending"></span> очікує</span>
        <span><span class="dot s-offline"></span> офлайн</span>
        <span><span class="dot s-inactive"></span> неактивний</span>
        <span><span class="dot s-reserve"></span> резерв (стан номера)</span>
    </div>
@endsection
