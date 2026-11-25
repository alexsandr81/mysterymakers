<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php
        $siteName = \App\Models\Setting::siteName();
        $siteTagline = \App\Models\Setting::tagline();
        $nameParts = preg_split('/\s+/u', trim($siteName), 2); // «Mystery Makers» → «Mystery» + «Makers»
    @endphp
    <title>@yield('title', $siteName) — {{ $siteName }}</title>
    <meta name="description" content="@yield('description', \App\Models\Setting::description() ?: $siteTagline)">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="@yield('description', \App\Models\Setting::description() ?: $siteTagline)">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')<meta property="og:image" content="@yield('og_image')">@endif
    @stack('head')
    @include('partials.theme-head')
    @include('partials.fonts-head')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $cartCount = app(\App\Services\Cart::class)->count();
    $menuCategories = \App\Models\Category::whereNull('parent_id')->with('children')->orderBy('sort')->get();
    $otherLocale = app()->getLocale() === 'uk' ? 'ru' : 'uk';
    $footerPages = \App\Models\Page::where('is_published', true)->where('show_in_footer', true)->orderBy('sort')->orderBy('id')->get();
@endphp

<div class="topbar">
    <div class="container topbar__inner">
        <span>{{ $siteTagline }}</span>
        <div class="topbar__right">
            <div class="langs">
                @foreach(['uk' => 'UA', 'ru' => 'RU'] as $code => $label)
                    <a href="{{ route('locale', $code) }}" class="langflag langflag--{{ $code }} {{ app()->getLocale() === $code ? 'is-active' : '' }}" title="{{ $code === 'uk' ? 'Українська' : 'Русский' }}" @if(app()->getLocale() === $code) aria-current="true" @endif><span>{{ $label }}</span></a>
                @endforeach
            </div>
            @include('partials.theme-button', ['title' => __('shop.theme')])
        </div>
    </div>
</div>

<header class="header">
    <div class="container header__inner">
        <a href="{{ route('home') }}" class="logo">{{ $nameParts[0] }}@if(!empty($nameParts[1]))<b>{{ $nameParts[1] }}</b>@endif</a>

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
            <div class="catnav__item">
                <a href="{{ route('catalog.category', $c) }}">{{ $c->name }}@if($c->children->isNotEmpty()) <small class="catnav__caret">▾</small>@endif</a>
                @if($c->children->isNotEmpty())
                    <div class="catnav__sub">
                        @foreach($c->children as $child)
                            <a href="{{ route('catalog.category', $child) }}">{{ $child->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
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
            <div class="logo logo--footer">{{ $nameParts[0] }}@if(!empty($nameParts[1]))<b>{{ $nameParts[1] }}</b>@endif</div>
            <p>{{ $siteTagline }}</p>
        </div>
        <div>
            <strong>{{ __('shop.categories') }}</strong>
            @foreach($menuCategories as $c)
                <a href="{{ route('catalog.category', $c) }}">{{ $c->name }}</a>
            @endforeach
        </div>
        @if($footerPages->isNotEmpty())
        <div>
            <strong>{{ __('shop.info') }}</strong>
            @foreach($footerPages as $p)
                <a href="{{ route('page', $p) }}">{{ $p->title }}</a>
            @endforeach
        </div>
        @endif
        <div>
            <strong>{{ __('shop.contacts') }}</strong>
            @foreach(\App\Models\Setting::phones() as $phone)
                @php $phoneDigits = \App\Models\Setting::digits($phone); @endphp
                @if($phoneDigits)
                    <a href="tel:+{{ $phoneDigits }}">📞 {{ $phone }}</a>
                    @if($loop->first)
                        <div class="messengers">
                            <a href="viber://chat?number=%2B{{ $phoneDigits }}" class="messenger messenger--viber">Viber</a>
                            <a href="https://wa.me/{{ $phoneDigits }}" target="_blank" rel="noopener" class="messenger messenger--wa">WhatsApp</a>
                            <a href="https://t.me/+{{ $phoneDigits }}" target="_blank" rel="noopener" class="messenger messenger--tg">Telegram</a>
                        </div>
                    @endif
                @else
                    <span>{{ $phone }}</span>
                @endif
            @endforeach
            @if(\App\Models\Setting::get('email'))<a href="mailto:{{ \App\Models\Setting::get('email') }}">✉ {{ \App\Models\Setting::get('email') }}</a>@endif
            @if(\App\Models\Setting::get('address'))<span>{{ \App\Models\Setting::get('address') }}</span>@endif
        </div>
    </div>
    <div class="footer__copy">© {{ date('Y') }} {{ $siteName }}. {{ __('shop.footer_rights') }}</div>
</footer>
@include('partials.password-toggle')
</body>
</html>
