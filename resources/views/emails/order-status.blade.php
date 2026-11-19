@extends('emails.layout')

@section('title', __('mail.status_subject', ['number' => $order->number]))

@section('content')
<h1 style="margin:0 0 6px;font-size:22px;color:#3a2a1c;">{{ __('mail.status_title', ['number' => $order->number]) }}</h1>
<p style="margin:0 0 4px;">{{ __('mail.hello', ['name' => $order->name]) }}</p>

<div style="margin:16px 0;padding:14px 16px;background:#fbf7f1;border:1px solid #e8dccb;border-radius:10px;">
    <span style="color:#8a7a68;">{{ __('mail.status_now') }}:</span>
    <strong style="font-size:18px;color:#8b5a2b;">{{ __('shop.statuses.' . $order->status) }}</strong>
    <div style="margin-top:6px;font-size:14px;">{{ __('mail.status_text.' . $order->status) }}</div>
</div>

@if($order->status !== 'canceled')
    @include('emails.partials.items')
@endif

@if($order->user_id)
    @include('emails.partials.button', ['url' => route('account'), 'label' => __('mail.my_orders')])
@endif
@endsection
