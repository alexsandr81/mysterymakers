@extends('layouts.app')

@php use App\Support\Money; @endphp

@section('title', __('shop.cart'))

@section('content')
<h1>{{ __('shop.cart') }}</h1>

@if($items->isEmpty())
    <p class="muted">{{ __('shop.cart_empty') }}</p>
    <a href="{{ route('catalog') }}" class="btn btn--brand">{{ __('shop.to_catalog') }}</a>
@else
    <div class="table-wrap">
    <table class="table">
        <thead>
            <tr>
                <th></th><th></th><th>{{ __('shop.price') }}</th><th>{{ __('shop.quantity') }}</th><th>{{ __('shop.sum') }}</th><th></th>
            </tr>
        </thead>
        <tbody>
        @foreach($items as $row)
            @php $p = $row['product']; @endphp
            <tr>
                <td class="thumb"><img src="{{ $p->main_image }}" alt=""></td>
                <td><a href="{{ route('product', $p) }}">{{ $p->name }}</a></td>
                <td>{{ Money::format($p->price) }} {{ __('shop.currency') }}</td>
                <td>
                    <form action="{{ route('cart.update', $p) }}" method="POST" class="qty-form">
                        @csrf @method('PATCH')
                        <input type="number" name="quantity" value="{{ $row['quantity'] }}" min="1" max="{{ min(99, max($p->stock, 1)) }}" class="qty" onchange="this.form.submit()">
                    </form>
                </td>
                <td><strong>{{ Money::format($row['subtotal']) }} {{ __('shop.currency') }}</strong></td>
                <td>
                    <form action="{{ route('cart.remove', $p->id) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="link-btn" title="{{ __('shop.remove') }}">✕</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>

    <div class="cart-total">
        <div>{{ __('shop.total') }}: <strong>{{ Money::format($total) }} {{ __('shop.currency') }}</strong></div>
        <a href="{{ route('checkout') }}" class="btn btn--accent btn--lg">{{ __('shop.checkout') }}</a>
    </div>
@endif
@endsection
