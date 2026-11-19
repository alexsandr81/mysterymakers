@extends('layouts.app')

@php use App\Support\Money; @endphp

@section('title', $product->name)
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 150))

@section('og_type', 'product')
@section('og_image', $product->main_image)

@push('head')
@php
    $ld = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product->name,
        'sku' => $product->sku,
        'image' => $product->image_urls,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) $product->description), 300),
        'offers' => [
            '@type' => 'Offer',
            'url' => route('product', $product),
            'priceCurrency' => 'UAH',
            'price' => number_format((float) $product->price, 2, '.', ''),
            'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ];
    if ($reviews->isNotEmpty()) {
        $ld['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($ratingAvg, 1),
            'reviewCount' => $reviews->count(),
        ];
    }
@endphp
<script type="application/ld+json">{!! json_encode($ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endpush

@section('content')
<nav class="crumbs">
    <a href="{{ route('home') }}">{{ \App\Models\Setting::siteName() }}</a> /
    <a href="{{ route('catalog') }}">{{ __('shop.catalog') }}</a> /
    <a href="{{ route('catalog.category', $product->category) }}">{{ $product->category->name }}</a>
</nav>

<div class="product">
    <div class="gallery">
        <div class="zoom" id="zoom-box">
            <img id="main-image" class="gallery__main" src="{{ $product->main_image }}" alt="{{ $product->name }}">
        </div>
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
        @if($reviews->isNotEmpty())
            <a href="#reviews" class="rating-link">@include('partials.stars', ['value' => $ratingAvg]) {{ number_format($ratingAvg, 1) }} · {{ __('shop.reviews_count', ['count' => $reviews->count()]) }}</a>
        @endif
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

        @include('partials.favorite-button', ['product' => $product, 'wide' => true])

        @if($product->in_stock)
            <form action="{{ route('cart.add', $product) }}" method="POST" class="buy">
                @csrf
                <input type="number" name="quantity" value="1" min="1" max="{{ min(99, $product->stock) }}" class="qty">
                <button type="submit" class="btn btn--accent btn--lg">🛒 {{ __('shop.buy') }}</button>
            </form>
        @endif

        @php
            // Группируем выбранные значения по характеристике: «Цвет: Белый, Тёмный»
            $specs = $product->characteristicValues
                ->groupBy('characteristic_id')
                ->sortBy(fn ($values) => $values->first()->characteristic->sort);
        @endphp
        @if($specs->isNotEmpty() || $product->size_text)
            <h3>{{ __('shop.specs') }}</h3>
            <table class="specs">
                @if($product->size_text)
                    <tr><th>{{ __('shop.size') }}</th><td>{{ $product->size_text }}</td></tr>
                @endif
                @foreach($specs as $values)
                    <tr>
                        <th>{{ $values->first()->characteristic->name }}</th>
                        <td>{{ $values->map(fn ($v) => $v->title)->implode(', ') }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        @if($product->description)
            <h3>{{ __('shop.description') }}</h3>
            <p>{!! nl2br(e($product->description)) !!}</p>
        @endif
    </div>
</div>

<section id="reviews" class="reviews">
    <h2 class="section-title">{{ __('shop.reviews') }}@if($reviews->isNotEmpty()) <small class="muted">({{ $reviews->count() }})</small>@endif</h2>

    <div class="reviews__layout">
        <div class="reviews__list">
            @forelse($reviews as $review)
                <article class="review">
                    <div class="review__head">
                        @include('partials.stars', ['value' => $review->rating])
                        <strong>{{ $review->user?->name }}</strong>
                        <span class="muted">{{ $review->created_at->format('d.m.Y') }}</span>
                    </div>
                    <p>{!! nl2br(e($review->body)) !!}</p>
                </article>
            @empty
                <p class="muted">{{ __('shop.no_reviews') }}</p>
            @endforelse
        </div>

        <div class="reviews__form">
            @auth
                @if($myReview)
                    <p class="muted">
                        @if($myReview->status === 'pending') {{ __('shop.review_pending') }}
                        @elseif($myReview->status === 'approved') {{ __('shop.review_published') }}
                        @else {{ __('shop.review_rejected') }}
                        @endif
                    </p>
                @else
                    <h3>{{ __('shop.write_review') }}</h3>
                    <form action="{{ route('reviews.store', $product) }}" method="POST" class="form">
                        @csrf
                        <div class="rating-input">
                            @for($i = 5; $i >= 1; $i--)
                                <input type="radio" name="rating" id="star{{ $i }}" value="{{ $i }}" @checked((int) old('rating', 5) === $i)>
                                <label for="star{{ $i }}" title="{{ $i }}">★</label>
                            @endfor
                        </div>
                        <label>{{ __('shop.your_review') }}
                            <textarea name="body" rows="4" required minlength="3" maxlength="2000">{{ old('body') }}</textarea>
                        </label>
                        <button type="submit" class="btn btn--brand">{{ __('shop.send_review') }}</button>
                    </form>
                @endif
            @else
                <p class="muted"><a href="{{ route('login') }}">{{ __('shop.login') }}</a>, {{ __('shop.login_to_review') }}</p>
            @endauth
        </div>
    </div>
</section>

@if($related->isNotEmpty())
    <h2 class="section-title">{{ __('shop.related') }}</h2>
    <div class="grid">
        @foreach($related as $item)
            @include('partials.product-card', ['product' => $item])
        @endforeach
    </div>
@endif
<script>
(function () {
    // Увеличение фото при наведении: картинка увеличивается и «следит» за курсором (только там, где есть мышь)
    var box = document.getElementById('zoom-box');
    var img = document.getElementById('main-image');
    if (!box || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
    box.addEventListener('mousemove', function (e) {
        var r = box.getBoundingClientRect();
        img.style.transformOrigin = ((e.clientX - r.left) / r.width * 100) + '% ' + ((e.clientY - r.top) / r.height * 100) + '%';
        img.style.transform = 'scale(2.2)';
    });
    box.addEventListener('mouseleave', function () { img.style.transform = 'scale(1)'; });
})();
</script>
@endsection
