@extends('layouts.app')

@php use App\Support\Money; @endphp

@section('title', __('shop.account'))

@section('content')
<h1>{{ __('shop.account') }}</h1>

<div class="account">
    <section>
        <h3>{{ __('shop.profile') }}</h3>
        <form action="{{ route('account.update') }}" method="POST" class="form">
            @csrf @method('PATCH')
            <label>{{ __('shop.name') }}
                <input type="text" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name')<span class="err">{{ $message }}</span>@enderror
            </label>
            <label>{{ __('shop.email') }}
                <input type="email" value="{{ $user->email }}" disabled>
            </label>
            <label>{{ __('shop.phone') }}
                <input type="tel" name="phone" value="{{ old('phone', $user->phone) }}">
                @error('phone')<span class="err">{{ $message }}</span>@enderror
            </label>
            <button type="submit" class="btn btn--brand">{{ __('shop.save') }}</button>
        </form>
    </section>

    <section>
        <h3>{{ __('shop.my_orders') }}</h3>
        @forelse($orders as $order)
            <div class="order-box">
                <div class="order-box__head">
                    <strong>{{ $order->number }}</strong>
                    <span class="status status--{{ $order->status }}">{{ __('shop.statuses.' . $order->status) }}</span>
                </div>
                <div class="muted">{{ $order->created_at->format('d.m.Y H:i') }}</div>
                <ul>
                    @foreach($order->items as $item)
                        <li>{{ $item->name }} × {{ $item->quantity }} — {{ Money::format($item->price * $item->quantity) }}</li>
                    @endforeach
                </ul>
                <div>{{ __('shop.total') }}: <strong>{{ Money::format($order->total) }} {{ __('shop.currency') }}</strong></div>
            </div>
        @empty
            <p class="muted">{{ __('shop.no_orders') }}</p>
        @endforelse
    </section>
</div>
@endsection
