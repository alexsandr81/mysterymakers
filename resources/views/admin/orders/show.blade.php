@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Заказ ' . $order->number)

@section('content')
<div class="admin-head">
    <h1>Заказ {{ $order->number }}</h1>
    <a href="{{ route('admin.orders.index') }}" class="btn">← К списку</a>
</div>

<div class="order-grid">
    <section class="panel">
        <h3>Клиент</h3>
        <p><strong>{{ $order->name }}</strong></p>
        <p>{{ $order->phone }}</p>
        @if($order->email)<p>{{ $order->email }}</p>@endif
        <h3>Доставка и оплата</h3>
        <p>{{ __('shop.delivery_types.' . $order->delivery, [], 'ru') }}@if($order->address): {{ $order->address }}@endif</p>
        <p>{{ __('shop.payment_types.' . $order->payment, [], 'ru') }}</p>
        @if($order->comment)
            <h3>Комментарий</h3>
            <p>{{ $order->comment }}</p>
        @endif
    </section>

    <section class="panel">
        <h3>Статус</h3>
        <form action="{{ route('admin.orders.status', $order) }}" method="POST" class="inline-form">
            @csrf @method('PATCH')
            <select name="status">
                @foreach(\App\Models\Order::STATUSES as $s)
                    <option value="{{ $s }}" @selected($order->status === $s)>{{ __('shop.statuses.' . $s, [], 'ru') }}</option>
                @endforeach
            </select>
            <button class="btn btn--brand">Сохранить</button>
        </form>
        <p class="muted">Отмена заказа возвращает товары на склад.</p>
    </section>
</div>

<table class="table">
    <thead><tr><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
    <tbody>
    @foreach($order->items as $item)
        <tr>
            <td>{{ $item->name }}</td>
            <td>{{ Money::format($item->price) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ Money::format($item->price * $item->quantity) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="3" class="right"><strong>Итого</strong></td><td><strong>{{ Money::format($order->total) }} грн</strong></td></tr></tfoot>
</table>
@endsection
