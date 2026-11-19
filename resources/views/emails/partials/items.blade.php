@php use App\Support\Money; use App\Support\MailImage; @endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:16px 0;">
    @foreach($order->items as $item)
        @php
            $img = MailImage::forItem($item);
            $name = $item->product ? $item->product->name : $item->name;
        @endphp
        <tr>
            <td width="76" style="padding:10px 0;border-bottom:1px solid #eee3d3;vertical-align:middle;">
                @if($img)
                    <img src="{{ $message->embed($img) }}" width="64" height="64" alt="" style="display:block;width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #e8dccb;">
                @else
                    <div style="width:64px;height:64px;border-radius:8px;background:#f1e7da;text-align:center;line-height:64px;font-size:26px;">📦</div>
                @endif
            </td>
            <td style="padding:10px 8px;border-bottom:1px solid #eee3d3;vertical-align:middle;">
                @if($item->product)
                    <a href="{{ route('product', $item->product) }}" style="color:#3a2a1c;font-weight:bold;text-decoration:none;">{{ $name }}</a>
                @else
                    <strong>{{ $name }}</strong>
                @endif
                <div style="color:#8a7a68;font-size:13px;">{{ $item->quantity }} × {{ Money::format($item->price) }} {{ __('shop.currency') }}</div>
            </td>
            <td align="right" style="padding:10px 0;border-bottom:1px solid #eee3d3;vertical-align:middle;white-space:nowrap;font-weight:bold;">
                {{ Money::format($item->price * $item->quantity) }} {{ __('shop.currency') }}
            </td>
        </tr>
    @endforeach
    <tr>
        <td colspan="2" align="right" style="padding:14px 8px 0 0;color:#8a7a68;">{{ __('shop.total') }}:</td>
        <td align="right" style="padding:14px 0 0;font-size:18px;font-weight:bold;color:#8b5a2b;white-space:nowrap;">{{ Money::format($order->total) }} {{ __('shop.currency') }}</td>
    </tr>
</table>
