@extends('layouts.app')

@section('title', $page->title)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $page->body), 150))

@section('content')
<nav class="crumbs">
    <a href="{{ route('home') }}">{{ __('shop.brand') }}</a> / {{ $page->title }}
</nav>

<article class="prose">
    <h1>{{ $page->title }}</h1>
    {!! nl2br(e($page->body)) !!}
</article>
@endsection
