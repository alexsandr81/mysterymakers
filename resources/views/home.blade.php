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
        <div class="slider__dots">
            @foreach($banners as $banner)
                <button type="button" class="slider__dot {{ $loop->first ? 'is-active' : '' }}" aria-label="{{ $loop->iteration }}"></button>
            @endforeach
        </div>
    @endif
</section>

@if($banners->count() > 1)
<script>
(function () {
    var track = document.getElementById('slider-track');
    var dots = document.querySelectorAll('.slider__dot');
    var count = track.children.length;
    var timer = null;

    function current() { return Math.round(track.scrollLeft / track.clientWidth); }
    function goTo(index) {
        if (index >= count) index = 0;          // после последнего — снова первый (по кругу)
        if (index < 0) index = count - 1;
        track.scrollTo({ left: index * track.clientWidth, behavior: 'smooth' });
    }
    function start() { stop(); timer = setInterval(function () { goTo(current() + 1); }, 5000); }
    function stop() { if (timer) { clearInterval(timer); timer = null; } }

    document.getElementById('slider-prev').addEventListener('click', function () { goTo(current() - 1); start(); });
    document.getElementById('slider-next').addEventListener('click', function () { goTo(current() + 1); start(); });
    dots.forEach(function (d, i) { d.addEventListener('click', function () { goTo(i); start(); }); });

    track.addEventListener('scroll', function () {
        var c = current();
        dots.forEach(function (d, i) { d.classList.toggle('is-active', i === c); });
    }, { passive: true });

    // Пока курсор над слайдером — не крутим; после ухода (или касания) автопрокрутка продолжается
    track.addEventListener('mouseenter', stop);
    track.addEventListener('mouseleave', start);
    track.addEventListener('touchend', start, { passive: true });
    document.addEventListener('visibilitychange', function () { document.hidden ? stop() : start(); });

    start();
})();
</script>
@endif
@else
<section class="hero">
    <h1>{{ \App\Models\Setting::siteName() }}</h1>
    <p>{{ \App\Models\Setting::tagline() }}</p>
    <p class="hero__text">{{ \App\Models\Setting::description() }}</p>
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
