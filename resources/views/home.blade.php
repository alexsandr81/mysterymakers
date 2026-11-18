@extends('layouts.app')

@section('content')
@if($banners->isNotEmpty())
<section class="slider" aria-roledescription="carousel">
    <div class="slider__track" id="slider-track">
        @foreach($banners as $banner)
            <div class="slide">
                @if($banner->url)<a href="{{ $banner->url }}" class="slide__link">@endif
                    <img src="{{ $banner->image_url }}" alt="{{ $banner->title }}" @if(!$loop->first) loading="lazy" @endif>
                    @if($banner->title || $banner->subtitle)
                        <div class="slide__text">
                            @if($banner->title)<h2>{{ $banner->title }}</h2>@endif
                            @if($banner->subtitle)<p>{{ $banner->subtitle }}</p>@endif
                        </div>
                    @endif
                @if($banner->url)</a>@endif
            </div>
        @endforeach
    </div>
    @if($banners->count() > 1)
        <button type="button" class="slider__btn slider__btn--prev" id="slider-prev" aria-label="←">‹</button>
        <button type="button" class="slider__btn slider__btn--next" id="slider-next" aria-label="→">›</button>
    @endif
</section>

@if($banners->count() > 1)
<script>
(function () {
    var track = document.getElementById('slider-track');
    var count = track.children.length;
    function go(step) {
        var width = track.clientWidth;
        var index = Math.round(track.scrollLeft / width) + step;
        if (index >= count) index = 0;
        if (index < 0) index = count - 1;
        track.scrollTo({ left: index * width, behavior: 'smooth' });
    }
    document.getElementById('slider-prev').addEventListener('click', function () { go(-1); });
    document.getElementById('slider-next').addEventListener('click', function () { go(1); });
    var timer = setInterval(function () { go(1); }, 6000);
    ['pointerdown', 'wheel', 'touchstart'].forEach(function (name) {
        track.addEventListener(name, function () { clearInterval(timer); }, { passive: true });
    });
})();
</script>
@endif
@else
<section class="hero">
    <h1>{{ __('shop.brand') }}</h1>
    <p>{{ __('shop.tagline') }}</p>
    <a href="{{ route('catalog') }}" class="btn btn--accent btn--lg">{{ __('shop.catalog') }}</a>
</section>
@endif

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
