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
                <span style="font-size:26px;font-weight:bold;color:#ffffff;letter-spacing:.3px;">Mystery<span style="color:#f2b36b;">Makers</span></span>
                <div style="font-size:13px;color:#ecd9c2;margin-top:4px;">{{ __('mail.tagline') }}</div>
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
                    $shopPhone = \App\Models\Setting::get('phone');
                    $shopEmail = \App\Models\Setting::get('email');
                @endphp
                {{ __('mail.questions') }}
                @if($shopPhone) <a href="tel:{{ preg_replace('/[^\d+]/', '', $shopPhone) }}" style="color:#3a2a1c;font-weight:bold;text-decoration:none;">{{ $shopPhone }}</a>@endif
                @if($shopEmail) · <a href="mailto:{{ $shopEmail }}" style="color:#8b5a2b;">{{ $shopEmail }}</a>@endif
                <br><a href="{{ url('/') }}" style="color:#8b5a2b;">{{ parse_url(url('/'), PHP_URL_HOST) }}</a>
            </td>
        </tr>
    </table>
</td></tr>
</table>
</body>
</html>
