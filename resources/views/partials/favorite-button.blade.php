@php
    $isFav = auth()->check() && in_array($product->id, auth()->user()->favoriteIds(), true);
    $label = $isFav ? __('shop.favorite_remove') : __('shop.favorite_add');
@endphp
@auth
    <form action="{{ route('favorites.toggle', $product) }}" method="POST" class="fav {{ !empty($wide) ? 'fav--wide' : '' }}">
        @csrf
        <button type="submit" class="fav__btn {{ $isFav ? 'is-on' : '' }}" title="{{ $label }}" aria-label="{{ $label }}">
            {{ $isFav ? '♥' : '♡' }}@if(!empty($wide)) <span>{{ $label }}</span>@endif
        </button>
    </form>
@else
    <a href="{{ route('login') }}" class="fav {{ !empty($wide) ? 'fav--wide' : '' }}">
        <span class="fav__btn" title="{{ __('shop.favorite_add') }}">♡@if(!empty($wide)) <span>{{ __('shop.favorite_add') }}</span>@endif</span>
    </a>
@endauth
