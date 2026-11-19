@extends('layouts.app')

@section('title', $page->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) ($page->body ?: $page->sections->first()?->body)), 150))

@section('content')
<nav class="crumbs">
    <a href="{{ route('home') }}">{{ \App\Models\Setting::siteName() }}</a> / {{ $page->title }}
</nav>

<h1 class="page-title">{{ $page->title }}</h1>

<div class="info-cards">
    @if(trim((string) $page->body) !== '')
        <section class="info-card prose">
            {!! nl2br(e($page->body)) !!}
        </section>
    @endif

    @foreach($page->sections as $section)
        <section class="info-card prose">
            @if($section->title)<h2 class="info-card__title">{{ $section->title }}</h2>@endif
            @if(trim((string) $section->body) !== '')
                {!! nl2br(e($section->body)) !!}
            @endif
        </section>
    @endforeach
</div>
@endsection
