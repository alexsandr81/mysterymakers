@extends('emails.layout')

@section('title', __('shop.reset_mail_subject'))

@section('content')
<h1 style="margin:0 0 10px;font-size:22px;color:#3a2a1c;">{{ __('shop.reset_mail_subject') }}</h1>
<p style="margin:0 0 4px;">{{ __('shop.reset_mail_greeting') }}</p>
<p style="margin:0;">{{ __('shop.reset_mail_line1') }}</p>

@include('emails.partials.button', ['url' => $url, 'label' => __('shop.reset_mail_action')])

<p style="margin:0;color:#8a7a68;font-size:13px;">{{ __('shop.reset_mail_expire', ['minutes' => $minutes]) }} {{ __('shop.reset_mail_ignore') }}</p>
<p style="margin:14px 0 0;color:#8a7a68;font-size:12px;word-break:break-all;">{{ __('mail.link_fallback') }}<br><a href="{{ $url }}" style="color:#8b5a2b;">{{ $url }}</a></p>
@endsection
