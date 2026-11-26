<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MysteryMakers')</title>
</head>
<body style="margin:0;padding:0;background:#f4ece0;font-family:Arial,Helvetica,sans-serif;color:#3a2a1c;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4ece0;padding:24px 12px;">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;">
        <tr>
            <td style="background:#8b5a2b;border-radius:12px 12px 0 0;padding:22px 28px;">
                @php $nameParts = preg_split('/\s+/u', trim(\App\Models\Setting::siteName()), 2); @endphp
                <span style="display:inline-block;width:40px;height:40px;line-height:34px;text-align:center;border-radius:50%;background:#e9d3b4;border:3px solid #3a2a1c;color:#3a2a1c;font-family:Georgia,serif;font-weight:bold;font-size:17px;letter-spacing:-1px;vertical-align:middle;margin-right:10px;">MM</span><span style="vertical-align:middle;font-size:26px;font-weight:bold;color:#ffffff;letter-spacing:.3px;">{{ $nameParts[0] }}@if(!empty($nameParts[1]))<span style="color:#f2b36b;"> {{ $nameParts[1] }}</span>@endif</span>
                <div style="font-size:13px;color:#ecd9c2;margin-top:4px;">{{ \App\Models\Setting::tagline() }}</div>
            </td>
        </tr>
        <tr>
            <td style="background:#ffffff;padding:28px;border-left:1px solid #e8dccb;border-right:1px solid #e8dccb;font-size:15px;line-height:1.55;">
                @yield('content')
            </td>
        </tr>
        <tr>
            <td style="background:#fbf7f1;border:1px solid #e8dccb;border-top:0;border-radius:0 0 12px 12px;padding:18px 28px;font-size:13px;color:#8a7a68;">
                @php
                    $shopPhones = \App\Models\Setting::phones();
                    $shopEmail = \App\Models\Setting::get('email');
                @endphp
                {{ __('mail.questions') }}
                @foreach($shopPhones as $shopPhone) <a href="tel:{{ preg_replace('/[^\d+]/', '', $shopPhone) }}" style="color:#3a2a1c;font-weight:bold;text-decoration:none;">{{ $shopPhone }}</a>@if(!$loop->last) · @endif @endforeach
                @if($shopEmail) · <a href="mailto:{{ $shopEmail }}" style="color:#8b5a2b;">{{ $shopEmail }}</a>@endif
                <br><a href="{{ url('/') }}" style="color:#8b5a2b;">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>
            </td>
        </tr>
    </table>
</td></tr>
</table>
</body>
</html>
