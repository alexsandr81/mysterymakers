@extends('layouts.app')

@php use App\Support\Money; @endphp

@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150))

@section('content')
<nav class="crumbs">
    <a href="{{ route('home') }}">{{ __('shop.brand') }}</a> /
    <a href="{{ route('catalog') }}">{{ __('shop.catalog') }}</a> /
    <a href="{{ route('catalog.category', $product->category) }}">{{ $product->category->name }}</a>
</nav>

<div class="product">
    <div class="gallery">
        <img id="main-image" class="gallery__main" src="{{ $product->main_image }}" alt="{{ $product->name }}">
        @if(count($product->image_urls) > 1)
            <div class="gallery__thumbs">
                @foreach($product->image_urls as $url)
                    <img src="{{ $url }}" alt="" onclick="document.getElementById('main-image').src = this.src">
                @endforeach
            </div>
        @endif
    </div>

    <div class="product__info">
        <h1>{{ $product->name }}</h1>
        <div class="muted">{{ __('shop.sku') }}: {{ $product->sku }}</div>
        <div class="stock {{ $product->in_stock ? 'stock--yes' : 'stock--no' }}">
            {{ $product->in_stock ? '✓ ' . __('shop.in_stock') : '✕ ' . __('shop.out_of_stock') }}
        </div>

        <div class="product__price">
            @if($product->has_discount)
                <s class="old">{{ Money::format($product->old_price) }} {{ __('shop.currency') }}</s>
                <span class="tag tag--sale">−{{ $product->discount_percent }}%</span>
            @endif
            <div class="now {{ $product->has_discount ? 'now--sale' : '' }}">{{ Money::format($product->price) }} {{ __('shop.currency') }}</div>
        </div>

        @if($product->in_stock)
            <form action="{{ route('cart.add', $product) }}" method="POST" class="buy">
                @csrf
                <input type="number" name="quantity" value="1" min="1" max="{{ min(99, $product->stock) }}" class="qty">
                <button type="submit" class="btn btn--accent btn--lg">🛒 {{ __('shop.buy') }}</button>
            </form>
        @endif

        @if($product->description)
            <h3>{{ __('shop.description') }}</h3>
            <p>{!! nl2br(e($product->description)) !!}</p>
        @endif
    </div>
</div>

@if($related->isNotEmpty())
    <h2 class="section-title">{{ __('shop.related') }}</h2>
    <div class="grid">
        @foreach($related as $item)
            @include('partials.product-card', ['product' => $item])
        @endforeach
    </div>
@endif
@endsection
