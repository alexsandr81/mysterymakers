<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('shop.brand')) — {{ __('shop.brand') }}</title>
    <meta name="description" content="@yield('description', __('shop.tagline'))">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $cartCount = app(\App\Services\Cart::class)->count();
    $menuCategories = \App\Models\Category::whereNull('parent_id')->orderBy('sort')->get();
    $otherLocale = app()->getLocale() === 'uk' ? 'ru' : 'uk';
@endphp

<div class="topbar">
    <div class="container topbar__inner">
        <span>{{ __('shop.tagline') }}</span>
        <div class="topbar__right">
            <a href="{{ route('locale', $otherLocale) }}" class="lang">{{ strtoupper($otherLocale) }}</a>
        </div>
    </div>
</div>

<header class="header">
    <div class="container header__inner">
        <a href="{{ route('home') }}" class="logo">Mystery<b>Makers</b></a>

        <a href="{{ route('catalog') }}" class="btn btn--brand header__catalog">☰ {{ __('shop.catalog') }}</a>

        <form action="{{ route('catalog') }}" method="GET" class="search">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ __('shop.search_placeholder') }}">
            <button type="submit" class="btn btn--accent">{{ __('shop.search') }}</button>
        </form>

        <nav class="header__actions">
            @auth
                @if(auth()->user()->is_admin)
                    <a href="{{ route('admin.dashboard') }}">⚙ {{ __('shop.admin') }}</a>
                @endif
                <a href="{{ route('account') }}">👤 {{ auth()->user()->name }}</a>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="link-btn">{{ __('shop.logout') }}</button>
                </form>
            @else
                <a href="{{ route('login') }}">👤 {{ __('shop.login') }}</a>
            @endauth
            <a href="{{ route('cart.index') }}" class="cart-link">🛒 {{ __('shop.cart') }}
                @if($cartCount > 0)<span class="badge">{{ $cartCount }}</span>@endif
            </a>
        </nav>
    </div>
</header>

<nav class="catnav">
    <div class="container catnav__inner">
        @foreach($menuCategories as $c)
            <a href="{{ route('catalog.category', $c) }}">{{ $c->name }}</a>
        @endforeach
    </div>
</nav>

<main class="container main">
    @if(session('status'))
        <div class="flash flash--ok">{{ session('status') }}</div>
    @endif
    @if($errors->any() && !request()->routeIs('login', 'register', 'checkout', 'account'))
        <div class="flash flash--err">{{ $errors->first() }}</div>
    @endif

    @yield('content')
</main>

<footer class="footer">
    <div class="container footer__inner">
        <div>
            <div class="logo logo--footer">Mystery<b>Makers</b></div>
            <p>{{ __('shop.tagline') }}</p>
        </div>
        <div>
            <strong>{{ __('shop.categories') }}</strong>
            @foreach($menuCategories as $c)
                <a href="{{ route('catalog.category', $c) }}">{{ $c->name }}</a>
            @endforeach
        </div>
        <div>
            <strong>{{ __('shop.contacts') }}</strong>
            <span>+380 00 000 00 00</span>
            <span>info@mysterymakers.local</span>
        </div>
    </div>
    <div class="footer__copy">© {{ date('Y') }} MysteryMakers. {{ __('shop.footer_rights') }}</div>
</footer>
</body>
</html>
