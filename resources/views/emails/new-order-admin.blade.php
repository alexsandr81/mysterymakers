@extends('emails.layout')

@section('title', 'Новый заказ №' . $order->number)

@section('content')
@php use App\Support\Money; @endphp
<h1 style="margin:0 0 6px;font-size:22px;color:#3a2a1c;">🛒 Новый заказ № {{ $order->number }}</h1>
<p style="margin:0;color:#8a7a68;">{{ $order->created_at->format('d.m.Y H:i') }}</p>

@include('emails.partials.items')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
    <tr><td style="padding:4px 0;color:#8a7a68;width:130px;">Клиент</td><td style="padding:4px 0;"><strong>{{ $order->name }}</strong></td></tr>
    <tr><td style="padding:4px 0;color:#8a7a68;">Телефон</td><td style="padding:4px 0;"><a href="tel:{{ $order->phone }}" style="color:#8b5a2b;">{{ $order->phone }}</a></td></tr>
    @if($order->email)
    <tr><td style="padding:4px 0;color:#8a7a68;">Email</td><td style="padding:4px 0;">{{ $order->email }}</td></tr>
    @endif
    <tr><td style="padding:4px 0;color:#8a7a68;">Доставка</td><td style="padding:4px 0;">{{ __('shop.delivery_types.' . $order->delivery, [], 'ru') }}, {{ $order->address }}</td></tr>
    <tr><td style="padding:4px 0;color:#8a7a68;">Оплата</td><td style="padding:4px 0;">{{ __('shop.payment_types.' . $order->payment, [], 'ru') }}</td></tr>
    @if($order->comment)
    <tr><td style="padding:4px 0;color:#8a7a68;vertical-align:top;">Комментарий</td><td style="padding:4px 0;">{!! nl2br(e($order->comment)) !!}</td></tr>
    @endif
</table>

@include('emails.partials.button', ['url' => route('admin.orders.show', $order), 'label' => 'Открыть заказ в админке'])
@endsection
