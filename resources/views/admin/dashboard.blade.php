@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Дашборд')

@section('content')
<div class="admin-head">
    <h1>Дашборд</h1>
    <div class="period">
        @foreach([7 => '7 дней', 30 => '30 дней', 90 => '90 дней'] as $d => $label)
            <a href="{{ route('admin.dashboard', ['days' => $d]) }}" class="{{ $days === $d ? 'active' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
</div>

<div class="stats">
    <a href="{{ route('admin.orders.index') }}" class="stat"><span>Новых заказов</span><strong>{{ $newOrders }}</strong></a>
    <div class="stat"><span>Продажи сегодня</span><strong>{{ Money::format($salesToday) }} грн</strong></div>
    <div class="stat"><span>Выручка за {{ $days }} дн.</span><strong>{{ Money::format($revenue) }} грн</strong></div>
    <div class="stat"><span>Заказов за {{ $days }} дн.</span><strong>{{ $ordersCount }}</strong></div>
    <div class="stat"><span>Средний чек</span><strong>{{ Money::format($avgCheck) }} грн</strong></div>
    <a href="{{ route('admin.reviews.index') }}" class="stat"><span>Отзывов на модерации</span><strong>{{ $pendingReviews }}</strong></a>
</div>

<section class="panel">
    <h3>Выручка по дням (без отменённых)</h3>
    <div class="chart" style="--cols: {{ count($series) }}">
        @foreach($series as $point)
            <div class="chart__col" title="{{ $point['label'] }}: {{ Money::format($point['revenue']) }} грн, заказов: {{ $point['count'] }}">
                <div class="chart__bar" style="height: {{ $point['height'] }}%"></div>
                @if($days <= 30 || $loop->index % 5 === 0)<span class="chart__label">{{ $point['label'] }}</span>@endif
            </div>
        @endforeach
    </div>
</section>

<div class="dash-grid">
    <section class="panel">
        <h3>Топ товаров за {{ $days }} дн.</h3>
        @forelse($topProducts as $t)
            <div class="rowline">
                <span>{{ $t->name }}</span>
                <span class="muted">{{ (int) $t->qty }} шт. · {{ Money::format($t->revenue) }} грн</span>
            </div>
        @empty
            <p class="muted">Продаж пока нет</p>
        @endforelse
    </section>

    <section class="panel">
        <h3>Заказы по статусам</h3>
        @foreach(\App\Models\Order::STATUSES as $s)
            <div class="rowline">
                <span><span class="status status--{{ $s }}">{{ __('shop.statuses.' . $s, [], 'ru') }}</span></span>
                <strong>{{ $statusCounts[$s] ?? 0 }}</strong>
            </div>
        @endforeach
    </section>

    <section class="panel">
        <h3>Заканчиваются на складе</h3>
        @forelse($lowStockProducts as $p)
            <div class="rowline">
                <a href="{{ route('admin.products.edit', $p) }}">{{ $p->sku }} · {{ $p->name_ru }}</a>
                <strong class="text-warn">{{ $p->stock }}</strong>
            </div>
        @empty
            <p class="muted">Все товары в достаточном количестве</p>
        @endforelse
    </section>
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
