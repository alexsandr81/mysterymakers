@extends('layouts.app')

@section('title', __('shop.order_thanks'))

@section('content')
<div class="center-box">
    <h1>{{ __('shop.order_thanks') }}</h1>
    <p>{{ __('shop.order_number') }}: <strong>{{ $number }}</strong></p>
    <p class="muted">{{ __('shop.order_thanks_text') }}</p>
    <a href="{{ route('catalog') }}" class="btn btn--brand">{{ __('shop.to_catalog') }}</a>
</div>
@endsection
