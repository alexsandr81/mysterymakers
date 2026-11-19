@php use App\Support\Money; @endphp
<article class="card">
    <a href="{{ route('product', $product) }}" class="card__img">
        <img src="{{ $product->main_image }}" alt="{{ $product->name }}" loading="lazy">
        @if($product->has_discount)
            <span class="tag tag--sale">−{{ $product->discount_percent }}%</span>
        @endif
    </a>
    @include('partials.favorite-button', ['product' => $product])
    <a href="{{ route('product', $product) }}" class="card__title">{{ $product->name }}</a>
    @if(($product->reviews_count ?? 0) > 0)
        <div class="card__rating">@include('partials.stars', ['value' => $product->reviews_avg]) <span class="muted">({{ $product->reviews_count }})</span></div>
    @endif
    <div class="card__price">
        @if($product->has_discount)
            <s class="old">{{ Money::format($product->old_price) }}</s>
        @endif
        <span class="now {{ $product->has_discount ? 'now--sale' : '' }}">{{ Money::format($product->price) }} {{ __('shop.currency') }}</span>
    </div>
    @if($product->in_stock)
        <form action="{{ route('cart.add', $product) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn--accent btn--block">🛒 {{ __('shop.add_to_cart') }}</button>
        </form>
    @else
        <div class="muted">{{ __('shop.out_of_stock') }}</div>
    @endif
</article>
