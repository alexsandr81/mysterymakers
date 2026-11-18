<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Админка') — MysteryMakers</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body class="admin">
<div class="admin-shell">
    <aside class="admin-side">
        <a href="{{ route('admin.dashboard') }}" class="logo logo--footer">Mystery<b>Makers</b></a>
        <nav>
            <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">📊 Дашборд</a>
            <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">🛒 Заказы</a>
            <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">📦 Товары</a>
            <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">📂 Категории</a>
            <a href="{{ route('admin.characteristics.index') }}" class="{{ request()->routeIs('admin.characteristics.*') ? 'active' : '' }}">🏷 Характеристики</a>
            <a href="{{ route('admin.banners.index') }}" class="{{ request()->routeIs('admin.banners.*') ? 'active' : '' }}">🖼 Баннеры</a>
            <a href="{{ route('admin.pages.index') }}" class="{{ request()->routeIs('admin.pages.*') ? 'active' : '' }}">📄 Страницы</a>
            <a href="{{ route('home') }}">↩ На сайт</a>
        </nav>
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
</body>
</html>
