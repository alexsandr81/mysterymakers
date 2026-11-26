<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('partials.theme-head')
    <title>@yield('title')</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="container err-page">
    <div class="err-page__code">@yield('code')</div>
    <h1>@yield('heading')</h1>
    <p class="muted">@yield('text')</p>
    <p>
        @if(auth()->check() && auth()->user()->isStaff() && request()->is('admin*'))
            <a href="{{ route('admin.dashboard') }}" class="btn btn--accent">← В админку</a>
        @endif
        <a href="{{ url('/') }}" class="btn">На главную</a>
    </p>
</main>
</body>
</html>
