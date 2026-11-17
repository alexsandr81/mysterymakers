@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Дашборд')

@section('content')
<h1>Дашборд</h1>

<div class="stats">
    <div class="stat"><span>Новых заказов</span><strong>{{ $newOrders }}</strong></div>
    <div class="stat"><span>Продажи сегодня</span><strong>{{ Money::format($salesToday) }} грн</strong></div>
    <div class="stat"><span>Продажи за месяц</span><strong>{{ Money::format($salesMonth) }} грн</strong></div>
    <div class="stat"><span>Товаров с остатком &lt; 5</span><strong>{{ $lowStock }}</strong></div>
</div>

<h2>Последние заказы</h2>
<table class="table">
    <thead><tr><th>№</th><th>Дата</th><th>Клиент</th><th>Сумма</th><th>Статус</th></tr></thead>
    <tbody>
    @forelse($latestOrders as $o)
        <tr>
            <td><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td>
            <td>{{ $o->created_at->format('d.m.Y H:i') }}</td>
            <td>{{ $o->name }}</td>
            <td>{{ Money::format($o->total) }} грн</td>
            <td><span class="status status--{{ $o->status }}">{{ __('shop.statuses.' . $o->status, [], 'ru') }}</span></td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">Заказов пока нет</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
