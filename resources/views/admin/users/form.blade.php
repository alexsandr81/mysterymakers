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
        <h3>Последние заказы</h3>
        @forelse($orders as $o)
            <p>
                <a href="{{ route('admin.orders.show', $o) }}">№ {{ $o->number }}</a>
                — {{ Money::format($o->total) }} грн,
                {{ __('shop.statuses.' . $o->status, [], 'ru') }},
                {{ $o->created_at->format('d.m.Y') }}
            </p>
        @empty
            <p class="muted">Заказов нет</p>
        @endforelse
    </section>
</div>
@endsection
