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
        <a href="{{ route('password.request') }}" class="forgot-link">{{ __('shop.forgot_password') }}</a>
        <button type="submit" class="btn btn--accent btn--block">{{ __('shop.login') }}</button>
    </form>
    @if(config('shop.google.client_id') && config('shop.google.client_secret'))
        <div class="divider"><span>{{ __('shop.or') }}</span></div>
        <a href="{{ route('google.redirect') }}" class="btn btn--google btn--block">
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9.1 3.6l6.8-6.8C35.8 2.4 30.3 0 24 0 14.6 0 6.5 5.4 2.6 13.2l7.9 6.1C12.4 13.6 17.7 9.5 24 9.5z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.6 3-2.3 5.5-4.8 7.2l7.6 5.9c4.4-4.1 7-10.1 7-17.6z"/><path fill="#FBBC05" d="M10.5 28.7a14.5 14.5 0 0 1 0-9.4l-7.9-6.1a24 24 0 0 0 0 21.6l7.9-6.1z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.6-5.9c-2.1 1.4-4.8 2.3-8.3 2.3-6.3 0-11.6-4.1-13.5-9.8l-7.9 6.1C6.5 42.6 14.6 48 24 48z"/></svg>
            {{ __('shop.login_google') }}
        </a>
    @endif
    <p class="muted">{{ __('shop.no_account') }} <a href="{{ route('register') }}">{{ __('shop.register') }}</a></p>
</div>
@endsection
