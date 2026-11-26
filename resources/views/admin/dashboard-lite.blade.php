@extends('admin.layout')

@section('title', 'Дашборд')

@section('content')
<h1>Здравствуйте, {{ auth()->user()->name }}!</h1>
<p class="muted">Вам доступны эти разделы:</p>
<div class="stats">
    @forelse(\App\Support\AdminSections::LIST as $key => [$icon, $label, $routeName])
        @if(auth()->user()->canAccess($key))
            <a href="{{ route($routeName) }}" class="stat"><span>{{ $icon }}</span><strong>{{ $label }}</strong></a>
        @endif
    @empty
    @endforelse
</div>
@unless(collect(\App\Support\AdminSections::keys())->contains(fn ($k) => auth()->user()->canAccess($k)))
    <p class="muted">Администратор пока не открыл вам ни одного раздела.</p>
@endunless
@endsection
