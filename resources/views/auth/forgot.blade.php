@extends('layouts.app')

@section('title', __('shop.forgot_password'))

@section('content')
<div class="center-box">
    <div class="auth-logo">@include('partials.logo-seal', ['variant' => 'full', 'size' => 132, 'logoLabel' => \App\Models\Setting::siteName()])</div>
    <h1>{{ __('shop.forgot_password') }}</h1>
    <p class="muted">{{ __('shop.forgot_hint') }}</p>
    <form action="{{ route('password.email') }}" method="POST" class="form">
        @csrf
        <label>{{ __('shop.email') }}
            <input type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </label>
        <button type="submit" class="btn btn--accent btn--block">{{ __('shop.send_reset_link') }}</button>
    </form>
    <p class="muted"><a href="{{ route('login') }}">← {{ __('shop.login') }}</a></p>
</div>
@endsection
