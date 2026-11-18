@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Пользователь')

@section('content')
<div class="admin-head">
    <h1>Пользователь: {{ $user->name }}</h1>
    <a href="{{ route('admin.users.index') }}" class="btn">← К списку</a>
</div>

<div class="order-grid">
    <section class="panel">
        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="form">
            @csrf @method('PUT')
            <label>Имя
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required>
            </label>
            <label>Телефон
                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}">
            </label>
            <label>Новый пароль (оставьте пустым, чтобы не менять)
                <input type="password" name="password" minlength="8" autocomplete="new-password">
            </label>
            <label class="check">
                <input type="hidden" name="is_admin" value="0">
                <input type="checkbox" name="is_admin" value="1" @checked(old('is_admin', $user->is_admin))> Администратор (доступ к админке)
            </label>
            @if($user->google_id)<p class="muted">Привязан вход через Google.</p>
            @endif
            <p class="muted">Избранных товаров: {{ $favoritesCount }}</p>
            <button class="btn btn--accent">Сохранить</button>
        </form>
    </section>

    <section class="panel">
        <h3>История заказов ({{ $orders->count() }})</h3>
        @php $spent = $orders->where('status', '!=', 'canceled')->sum('total'); @endphp
        <p class="muted">Сумма без отменённых: <strong>{{ Money::format($spent) }} грн</strong></p>
        @forelse($orders as $o)
            <div class="order-box">
                <div class="order-box__head">
                    <a href="{{ route('admin.orders.show', $o) }}"><strong>№ {{ $o->number }}</strong></a>
                    <span class="status status--{{ $o->status }}">{{ __('shop.statuses.' . $o->status, [], 'ru') }}</span>
                </div>
                <div class="muted">{{ $o->created_at->format('d.m.Y H:i') }} · {{ Money::format($o->total) }} грн</div>
                <ul class="order-items">
                    @foreach($o->items as $item)
                        <li>
                            <img src="{{ $item->image_url }}" alt="">
                            <span>
                                @if($item->product)<a href="{{ route('product', $item->product) }}" target="_blank">{{ $item->product->sku }} · {{ $item->product->name_ru }}</a>@else{{ $item->name }}@endif
                                × {{ $item->quantity }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="muted">Заказов нет</p>
        @endforelse
    </section>
</div>
@endsection
