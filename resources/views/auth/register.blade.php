@extends('layouts.app')

@section('title', __('shop.register'))

@section('content')
<div class="center-box">
    <h1>{{ __('shop.register') }}</h1>
    <form action="{{ route('register') }}" method="POST" class="form">
        @csrf
        <label>{{ __('shop.name') }}
            <input type="text" name="name" value="{{ old('name') }}" required autofocus>
            @error('name')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.email') }}
            <input type="email" name="email" value="{{ old('email') }}" required>
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.phone') }}
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="+380501234567">
            @error('phone')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.password') }}
            <input type="password" name="password" required minlength="8">
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.password_confirm') }}
            <input type="password" name="password_confirmation" required minlength="8">
        </label>
        <button type="submit" class="btn btn--accent btn--block">{{ __('shop.register') }}</button>
    </form>
    <p class="muted">{{ __('shop.have_account') }} <a href="{{ route('login') }}">{{ __('shop.login') }}</a></p>
</div>
@endsection
