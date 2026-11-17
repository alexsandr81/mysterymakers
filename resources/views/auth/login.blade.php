@extends('layouts.app')

@section('title', __('shop.login'))

@section('content')
<div class="center-box">
    <h1>{{ __('shop.login') }}</h1>
    <form action="{{ route('login') }}" method="POST" class="form">
        @csrf
        <label>{{ __('shop.email') }}
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.password') }}
            <input type="password" name="password" required>
        </label>
        <label class="check"><input type="checkbox" name="remember" value="1"> {{ __('shop.remember') }}</label>
        <button type="submit" class="btn btn--accent btn--block">{{ __('shop.login') }}</button>
    </form>
    <p class="muted">{{ __('shop.no_account') }} <a href="{{ route('register') }}">{{ __('shop.register') }}</a></p>
</div>
@endsection
