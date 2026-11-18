@extends('layouts.app')

@section('title', $category?->name ?? ($q !== '' ? __('shop.found_for') . ' «' . $q . '»' : __('shop.all_products')))

@section('content')
@php $topCategories = \App\Models\Category::whereNull('parent_id')->with('children')->orderBy('sort')->get(); @endphp

<div class="layout">
    <aside class="sidebar">
        <h3>{{ __('shop.categories') }}</h3>
        <ul class="tree">
            <li><a href="{{ route('catalog') }}" class="{{ !$category ? 'active' : '' }}">{{ __('shop.all_products') }}</a></li>
            @foreach($topCategories as $c)
                <li>
                    <a href="{{ route('catalog.category', $c) }}" class="{{ $category?->id === $c->id ? 'active' : '' }}">{{ $c->name }}</a>
                    @if($c->children->isNotEmpty())
                        <ul>
                            @foreach($c->children as $child)
                                <li><a href="{{ route('catalog.category', $child) }}" class="{{ $category?->id === $child->id ? 'active' : '' }}">{{ $child->name }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>

        <form method="GET" class="filters">
            <h3>{{ __('shop.filters') }}</h3>
            @if($q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
            <label>{{ __('shop.price_from') }}</label>
            <div class="row">
                <input type="number" name="min" min="0" value="{{ request('min') }}">
                <span>{{ __('shop.price_to') }}</span>
                <input type="number" name="max" min="0" value="{{ request('max') }}">
            </div>
            <label class="check"><input type="checkbox" name="in_stock" value="1" @checked(request()->boolean('in_stock'))> {{ __('shop.only_in_stock') }}</label>

            @foreach($filters as $characteristic)
                <div class="filter-group">
                    <strong>{{ $characteristic->name }}</strong>
                    @foreach($characteristic->values as $value)
                        <label class="check">
                            <input type="checkbox" name="attr[{{ $characteristic->id }}][]" value="{{ $value->id }}"
                                   @checked(in_array($value->id, $selectedValues, true))>
                            {{ $value->title }}
                        </label>
                    @endforeach
                </div>
            @endforeach

            <input type="hidden" name="sort" value="{{ $sort }}">
            <button type="submit" class="btn btn--brand btn--block">{{ __('shop.apply') }}</button>
            @if(request()->hasAny(['min', 'max', 'in_stock', 'attr']))
                <a href="{{ $category ? route('catalog.category', $category) : route('catalog') }}" class="reset-link">{{ __('shop.reset') }}</a>
            @endif
        </form>
    </aside>

    <section class="content">
        <div class="content__head">
            <h1>
                @if($q !== '') {{ __('shop.found_for') }} «{{ $q }}»
                @else {{ $category?->name ?? __('shop.all_products') }}
                @endif
            </h1>
            {{-- Сортировка: каждая опция — готовая ссылка, все текущие фильтры сохраняются --}}
            <div class="sort">
                <select onchange="window.location = this.value" aria-label="{{ __('shop.sort') }}">
                    @foreach(['new' => 'sort_new', 'price_asc' => 'sort_price_asc', 'price_desc' => 'sort_price_desc'] as $key => $label)
                        <option value="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}" @selected($sort === $key)>{{ __('shop.' . $label) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if($products->count())
            <div class="grid">
                @foreach($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
            <div class="pager">{{ $products->links('pagination::default') }}</div>
        @else
            <p class="muted">{{ __('shop.nothing_found') }}</p>
        @endif
    </section>
</div>
@endsection
