@extends('layouts.app')

@section('title', __('shop.new_password'))

@section('content')
<div class="center-box">
    <div class="auth-logo">@include('partials.logo-seal', ['variant' => 'full', 'size' => 132, 'label' => \App\Models\Setting::siteName()])</div>
    <h1>{{ __('shop.new_password') }}</h1>
    <form action="{{ route('password.update') }}" method="POST" class="form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <label>{{ __('shop.email') }}
            <input type="email" name="email" value="{{ old('email', $email) }}" required>
            @error('email')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.password') }}
            <input type="password" name="password" required minlength="8" autofocus autocomplete="new-password">
            @error('password')<span class="err">{{ $message }}</span>@enderror
        </label>
        <label>{{ __('shop.password_confirm') }}
            <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn--accent btn--block">{{ __('shop.save') }}</button>
    </form>
</div>
@endsection
