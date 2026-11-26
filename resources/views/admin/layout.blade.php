<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>@yield('title', 'Админка') — MysteryMakers</title>
    @include('partials.theme-head')
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @stack('head')
</head>
<body class="admin">
<div class="admin-shell">
    <aside class="admin-side">
        <a href="{{ route('admin.dashboard') }}" class="logo logo--footer">Mystery<b>Makers</b></a>
        <nav>
            @php
                $isAdmin = auth()->user()->is_admin;
                $toReview = $isAdmin ? \App\Models\Product::where('needs_review', true)->count() : 0;
                $actUnseen = $isAdmin ? \App\Models\Activity::unseenCount(auth()->user()) : 0;
                $newOrders = \App\Models\Order::where('status', 'new')->count();
                $pendingReviews = \App\Models\Review::where('status', 'pending')->count();
            @endphp
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">📊 Дашборд</a>
            <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">🛒 Заказы <span class="badge" id="orders-badge" @if(!$newOrders) hidden @endif>{{ $newOrders }}</span></a>
            <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">📦 Товары@if($toReview) <span class="badge" title="Ждут проверки">{{ $toReview }}</span>@endif</a>
            <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">💬 Отзывы@if($pendingReviews) <span class="badge">{{ $pendingReviews }}</span>@endif</a>
            @if($isAdmin)
            <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">👥 Пользователи</a>
            <a href="{{ route('admin.activity.index') }}" class="{{ request()->routeIs('admin.activity.*') ? 'active' : '' }}">🔔 Действия модератора <span class="badge" id="activity-badge" @if(!$actUnseen) hidden @endif>{{ $actUnseen }}</span></a>
            @endif
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">📂 Категории</a>
            <a href="{{ route('admin.holidays.index') }}" class="{{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}">🎉 Праздники</a>
            <a href="{{ route('admin.characteristics.index') }}" class="{{ request()->routeIs('admin.characteristics.*') ? 'active' : '' }}">🏷 Характеристики</a>
            <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">🖼 Баннеры</a>
            <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">📄 Страницы</a>
            <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">⚙ Настройки</a>
            <a href="{{ route('admin.calculator.index') }}" class="{{ request()->routeIs('admin.calculator.*') ? 'active' : '' }}">🧮 Калькулятор</a>
            <a href="{{ route('admin.novaposhta.edit') }}" class="{{ request()->routeIs('admin.novaposhta.*') ? 'active' : '' }}">📦 Новая Почта</a>
            <a href="{{ route('admin.backups.index') }}" class="{{ request()->routeIs('admin.backups.*') ? 'active' : '' }}">💾 Копии</a>
            <a href="{{ route('home') }}">↩ На сайт</a>
        </nav>
        @include('partials.theme-button', ['class' => 'link-btn link-btn--light', 'title' => 'Тема'])
        <button type="button" class="link-btn link-btn--light" id="sound-toggle" title="Звук при новом заказе">🔕 Звук выключен</button>
        @unless(auth()->user()->is_admin)<span class="muted role-note">Роль: модератор</span>@endunless
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="link-btn link-btn--light">Выйти</button>
        </form>
    </aside>
    <div class="admin-main">
        @if(session('status'))<div class="flash flash--ok">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="flash flash--err">{{ $errors->first() }}</div>@endif
        @yield('content')
    </div>
</div>
@include('partials.password-toggle')
<div class="order-toast" id="order-toast" hidden></div>
<script>
(function () {
    var badge = document.getElementById('orders-badge'), btn = document.getElementById('sound-toggle'), toast = document.getElementById('order-toast');
    var actBadge = document.getElementById('activity-badge');
    var lastAct = {{ $isAdmin ? (int) \App\Models\Activity::max('id') : 0 }};
    var lastId = {{ (int) \App\Models\Order::max('id') }}, baseTitle = document.title, ctx = null;
    var soundOn = false;
    try { soundOn = localStorage.getItem('mm_sound') === '1'; } catch (e) {}

    function paintBtn() { btn.textContent = soundOn ? '🔔 Звук включён' : '🔕 Звук выключен'; }
    function audio() {
        if (!ctx) { var C = window.AudioContext || window.webkitAudioContext; if (C) ctx = new C(); }
        if (ctx && ctx.state === 'suspended') ctx.resume();
        return ctx;
    }
    function beep() {
        var c = audio(); if (!c) return;
        [[880, 0], [1175, 0.18], [1568, 0.36]].forEach(function (n) {
            var o = c.createOscillator(), g = c.createGain();
            o.type = 'sine'; o.frequency.value = n[0]; o.connect(g); g.connect(c.destination);
            var t = c.currentTime + n[1];
            g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.25, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.28);
            o.start(t); o.stop(t + 0.3);
        });
    }
    btn.addEventListener('click', function () {
        soundOn = !soundOn;
        try { localStorage.setItem('mm_sound', soundOn ? '1' : '0'); } catch (e) {}
        paintBtn();
        if (soundOn) { audio(); beep(); }
    });
    // Браузер разрешает звук только после действия на странице — «разблокируем» при первом клике
    document.addEventListener('click', function () { if (soundOn) audio(); }, { once: true });
    paintBtn();

    function showCount(n) {
        badge.textContent = n; badge.hidden = !n;
        document.title = (n ? '(' + n + ') ' : '') + baseTitle;
    }
    showCount({{ (int) $newOrders }});

    function poll() {
        fetch(@json(route('admin.orders.poll')), { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                showCount(d.new);
                if (actBadge && d.act_unseen !== undefined) { actBadge.textContent = d.act_unseen; actBadge.hidden = !d.act_unseen; }
                if (d.act_last_id > lastAct) {
                    lastAct = d.act_last_id;
                    toast.textContent = '🔔 ' + d.act_text;
                    toast.hidden = false;
                    setTimeout(function () { toast.hidden = true; }, 12000);
                    if (soundOn) beep();
                }
                if (d.last_id > lastId) {
                    lastId = d.last_id;
                    toast.textContent = '🛒 Новый заказ № ' + d.number + ' на ' + d.total + ' грн';
                    toast.hidden = false;
                    setTimeout(function () { toast.hidden = true; }, 12000);
                    if (soundOn) beep();
                }
            }).catch(function () {});
    }
    setInterval(poll, 20000);
})();
</script>
</body>
</html>
