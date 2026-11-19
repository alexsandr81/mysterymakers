@extends('emails.layout')

@section('title', __('mail.order_placed_subject', ['number' => $order->number]))

@section('content')
<h1 style="margin:0 0 6px;font-size:22px;color:#3a2a1c;">{{ __('mail.order_placed_title') }}</h1>
<p style="margin:0 0 4px;">{{ __('mail.hello', ['name' => $order->name]) }}</p>
<p style="margin:0;">{{ __('mail.order_placed_text') }}</p>

<div style="margin:18px 0 4px;padding:12px 16px;background:#fbf7f1;border:1px solid #e8dccb;border-radius:10px;">
    <span style="color:#8a7a68;">{{ __('shop.order_number') }}:</span>
    <strong style="font-size:18px;color:#8b5a2b;">№ {{ $order->number }}</strong>
</div>

@include('emails.partials.items')

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">
    <tr>
        <td style="padding:4px 0;color:#8a7a68;width:130px;">{{ __('mail.delivery') }}</td>
        <td style="padding:4px 0;">{{ __('shop.delivery_types.' . $order->delivery) }}@if($order->address), {{ $order->address }}@endif</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#8a7a68;">{{ __('mail.payment') }}</td>
        <td style="padding:4px 0;">{{ __('shop.payment_types.' . $order->payment) }}</td>
    </tr>
    <tr>
        <td style="padding:4px 0;color:#8a7a68;">{{ __('mail.recipient') }}</td>
        <td style="padding:4px 0;">{{ $order->name }}, {{ $order->phone }}</td>
    </tr>
    @if($order->comment)
    <tr>
        <td style="padding:4px 0;color:#8a7a68;vertical-align:top;">{{ __('mail.comment') }}</td>
        <td style="padding:4px 0;">{!! nl2br(e($order->comment)) !!}</td>
    </tr>
    @endif
</table>

@if($order->user_id)
    @include('emails.partials.button', ['url' => route('account'), 'label' => __('mail.my_orders')])
@endif
@endsection
