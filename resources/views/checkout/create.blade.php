@extends('layouts.app')

@php use App\Support\Money; @endphp

@section('title', __('shop.checkout'))

@section('content')
<h1>{{ __('shop.checkout') }}</h1>

@error('cart')<div class="flash flash--err">{{ $message }}</div>@enderror

<div class="checkout">
    <form action="{{ route('checkout.store') }}" method="POST" class="form">
        @csrf
        <h3>{{ __('shop.contact_data') }}</h3>

        <label>{{ __('shop.name') }}
            <input type="text" name="name" value="{{ old('name', $user?->name) }}" required>
            @error('name')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.phone') }}
            <input type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" placeholder="+380501234567" required>
            @error('phone')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.email') }}
            <input type="email" name="email" value="{{ old('email', $user?->email) }}">
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </label>

        <h3>{{ __('shop.delivery_method') }}</h3>
        <label>
            <select name="delivery">
                @foreach(__('shop.delivery_types') as $key => $label)
                    <option value="{{ $key }}" @selected(old('delivery') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label>{{ __('shop.address') }}
            <input type="text" name="address" value="{{ old('address') }}">
            @error('address')<span class="err">{{ $message }}</span>@enderror
        </label>

        <h3>{{ __('shop.payment_method') }}</h3>
        <label>
            <select name="payment">
                @foreach(__('shop.payment_types') as $key => $label)
                    <option value="{{ $key }}" @selected(old('payment') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>{{ __('shop.comment') }}
            <textarea name="comment" rows="3">{{ old('comment') }}</textarea>
        </label>

        <button type="submit" class="btn btn--accent btn--lg">{{ __('shop.confirm_order') }}</button>
    </form>

    <aside class="summary">
        <h3>{{ __('shop.cart') }}</h3>
        @foreach($items as $row)
            <div class="summary__row">
                <span>{{ $row['product']->name }} × {{ $row['quantity'] }}</span>
                <span>{{ Money::format($row['subtotal']) }}</span>
            </div>
        @endforeach
        <div class="summary__total">{{ __('shop.total') }}: <strong>{{ Money::format($total) }} {{ __('shop.currency') }}</strong></div>
    </aside>
</div>
@endsection
