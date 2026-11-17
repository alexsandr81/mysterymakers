@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Заказы')

@section('content')
<h1>Заказы</h1>

<div class="filters-inline">
    <a href="{{ route('admin.orders.index') }}" class="{{ !$status ? 'active' : '' }}">Все</a>
    @foreach(\App\Models\Order::STATUSES as $s)
        <a href="{{ route('admin.orders.index', ['status' => $s]) }}" class="{{ $status === $s ? 'active' : '' }}">{{ __('shop.statuses.' . $s, [], 'ru') }}</a>
    @endforeach
</div>

<table class="table">
    <thead><tr><th>№</th><th>Дата</th><th>Клиент</th><th>Телефон</th><th>Сумма</th><th>Статус</th></tr></thead>
    <tbody>
    @forelse($orders as $o)
        <tr>
            <td><a href="{{ route('admin.orders.show', $o) }}">{{ $o->number }}</a></td>
            <td>{{ $o->created_at->format('d.m.Y H:i') }}</td>
            <td>{{ $o->name }}</td>
            <td>{{ $o->phone }}</td>
            <td>{{ Money::format($o->total) }} грн</td>
            <td><span class="status status--{{ $o->status }}">{{ __('shop.statuses.' . $o->status, [], 'ru') }}</span></td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Заказов нет</td></tr>
    @endforelse
    </tbody>
</table>
<div class="pager">{{ $orders->links('pagination::default') }}</div>
@endsection
