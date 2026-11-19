@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Заказ ' . $order->number)

@section('content')
<div class="admin-head">
    <h1>Заказ {{ $order->number }}</h1>
    <div class="admin-head__actions">
        <a href="{{ route('admin.orders.index') }}" class="btn">← К списку</a>
        <form action="{{ route('admin.orders.destroy', $order) }}" method="POST" onsubmit="return confirm('Удалить заказ {{ $order->number }}? Это нельзя отменить.')">
            @csrf @method('DELETE')
            <button class="btn btn--danger">🗑 Удалить заказ</button>
        </form>
    </div>
</div>

<div class="order-grid">
    <section class="panel">
        <h3>Клиент</h3>
        <p>
            @if($order->user_id)
                <strong><a href="{{ route('admin.users.edit', $order->user_id) }}" title="Открыть клиента и историю его заказов">{{ $order->name }}</a></strong>
                <span class="muted">(зарегистрированный клиент)</span>
            @else
                <strong>{{ $order->name }}</strong> <span class="muted">(гость, без регистрации)</span>
            @endif
        </p>
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

        @if($order->delivery === 'nova_poshta')
            <h3>Накладная Новой Почты</h3>
            @if($order->np_ttn)
                <p><strong>{{ $order->np_ttn }}</strong></p>
                <p class="muted">{{ $order->np_status }}</p>
                <div class="inline-form">
                    <a class="btn" href="{{ route('admin.orders.ttn.print', $order) }}" target="_blank">🖨 Печать</a>
                    <form action="{{ route('admin.orders.ttn.refresh', $order) }}" method="POST">@csrf<button class="btn">↻ Статус</button></form>
                    <form action="{{ route('admin.orders.ttn.destroy', $order) }}" method="POST" onsubmit="return confirm('Удалить накладную {{ $order->np_ttn }}?')">@csrf @method('DELETE')<button class="btn btn--danger">Удалить ТТН</button></form>
                </div>
            @elseif($order->np_city_ref && $order->np_warehouse_ref)
                <form action="{{ route('admin.orders.ttn.store', $order) }}" method="POST" onsubmit="return confirm('Создать накладную в Новой Почте?')">
                    @csrf
                    <button class="btn btn--accent">📦 Создать накладную</button>
                </form>
                <p class="muted">Получатель оплачивает при получении{{ $order->payment === 'cod' ? ' ' . \App\Support\Money::format($order->total) . ' грн (наложенный платёж)' : '' }}.</p>
            @else
                <p class="muted">Город и отделение не выбраны из списка Новой Почты — накладную можно оформить вручную в кабинете my.novaposhta.ua.</p>
            @endif
        @endif
    </section>
</div>

<table class="table">
    <thead><tr><th></th><th>Артикул</th><th>Товар</th><th>Цена</th><th>Кол-во</th><th>Сумма</th></tr></thead>
    <tbody>
    @foreach($order->items as $item)
        <tr>
            <td class="thumb"><img src="{{ $item->image_url }}" alt=""></td>
            <td>@if($item->product)<a href="{{ route('product', $item->product) }}" title="Открыть страницу товара на сайте" target="_blank">{{ $item->product->sku }}</a>@else<span class="muted">—</span>@endif</td>
            <td>@if($item->product)<a href="{{ route('product', $item->product) }}" target="_blank">{{ $item->product->name_ru }}</a>@else{{ $item->name }} <span class="muted">(товар удалён)</span>@endif</td>
            <td>{{ Money::format($item->price) }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ Money::format($item->price * $item->quantity) }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot><tr><td colspan="5" class="right"><strong>Итого</strong></td><td><strong>{{ Money::format($order->total) }} грн</strong></td></tr></tfoot>
</table>
@endsection
