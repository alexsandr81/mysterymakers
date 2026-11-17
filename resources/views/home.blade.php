@extends('layouts.app')

@section('content')
<section class="hero">
    <h1>{{ __('shop.brand') }}</h1>
    <p>{{ __('shop.tagline') }}</p>
    <a href="{{ route('catalog') }}" class="btn btn--accent btn--lg">{{ __('shop.catalog') }}</a>
</section>

<section>
    <h2 class="section-title">{{ __('shop.categories') }}</h2>
    <div class="tiles">
        @foreach($categories as $c)
            <a href="{{ route('catalog.category', $c) }}" class="tile">{{ $c->name }}</a>
        @endforeach
    </div>
</section>

@if($sale->isNotEmpty())
<section>
    <h2 class="section-title">{{ __('shop.sale') }}</h2>
    <div class="grid">
        @foreach($sale as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endif

<section>
    <h2 class="section-title">{{ __('shop.new_arrivals') }}</h2>
    <div class="grid">
        @foreach($latest as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endsection
