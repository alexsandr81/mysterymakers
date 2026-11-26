{{--
  Логотип-печать MysteryMakers (круглая, слои фанеры, монограмма MM шрифтом Cinzel Decorative).
  Параметры: $variant = 'mini' (без надписи по кольцу — для шапки и подвала) или 'full' (с надписью, для страниц входа),
             $size — размер в пикселях, $logoLabel — подпись для скринридеров (нужна, если рядом нет названия сайта).
  Шрифт Cinzel Decorative подключается в layouts/app.blade.php; без интернета буквы нарисуются запасным шрифтом Georgia.
--}}
@php
    $variant = $variant ?? 'mini';
    $size = $size ?? 44;
    $sid = 'seal' . substr(md5(uniqid('', true)), 0, 6);
    $mmFont = "'Cinzel Decorative', Georgia, serif";
@endphp
<svg class="logo-seal" width="{{ $size }}" height="{{ $size }}" viewBox="-113 -113 226 226" xmlns="http://www.w3.org/2000/svg"
     @if(!empty($logoLabel)) role="img" aria-label="{{ $logoLabel }}" @else aria-hidden="true" @endif focusable="false">
    <circle r="113" fill="#3a2a1c"/>
    @if($variant === 'full')
        <defs>
            <path id="{{ $sid }}t" d="M-86,0 a86,86 0 0,1 172,0"/>
            <path id="{{ $sid }}b" d="M-96,0 a96,96 0 0,0 192,0"/>
        </defs>
        <circle r="107" fill="none" stroke="#8b5a2b" stroke-width="1.5"/>
        <circle r="100" fill="none" stroke="#c9975e" stroke-width="1"/>
        <circle r="76" fill="none" stroke="#c9975e" stroke-width="1"/>
        <text style="font-family:{{ $mmFont }};font-size:16px;font-weight:700;letter-spacing:4px;fill:#f1e7da"><textPath href="#{{ $sid }}t" startOffset="50%" text-anchor="middle">MYSTERY MAKERS</textPath></text>
        <text style="font-family:Georgia,serif;font-size:11px;letter-spacing:4px;fill:#e9d3b4"><textPath href="#{{ $sid }}b" startOffset="50%" text-anchor="middle">МАЙСТЕРНЯ З ФАНЕРИ</textPath></text>
        <path d="M-88 0 L-85 -4 L-81 0 L-85 4 Z" fill="#d9822b"/>
        <path d="M88 0 L85 -4 L81 0 L85 4 Z" fill="#d9822b"/>
        <circle r="72" fill="#6e4520"/>
        <circle r="67" fill="#8b5a2b"/>
        <circle r="62" fill="#b07c45"/>
        <circle r="57" fill="#e9d3b4"/>
        <text x="0" y="18" text-anchor="middle" style="font-family:{{ $mmFont }};font-size:54px;font-weight:700;letter-spacing:-2px;fill:#3a2a1c">MM</text>
        <line x1="-24" y1="34" x2="-7" y2="34" stroke="#d9822b" stroke-width="1.5"/>
        <line x1="7" y1="34" x2="24" y2="34" stroke="#d9822b" stroke-width="1.5"/>
        <path d="M-3.5 34 L0 30 L3.5 34 L0 38 Z" fill="#d9822b"/>
    @else
        <circle r="103" fill="none" stroke="#8b5a2b" stroke-width="3"/>
        <circle r="92" fill="#6e4520"/>
        <circle r="84" fill="#8b5a2b"/>
        <circle r="76" fill="#b07c45"/>
        <circle r="68" fill="#e9d3b4"/>
        <text x="0" y="22" text-anchor="middle" style="font-family:{{ $mmFont }};font-size:64px;font-weight:700;letter-spacing:-3px;fill:#3a2a1c">MM</text>
        <line x1="-30" y1="42" x2="-9" y2="42" stroke="#d9822b" stroke-width="3"/>
        <line x1="9" y1="42" x2="30" y2="42" stroke="#d9822b" stroke-width="3"/>
        <path d="M-5 42 L0 37 L5 42 L0 47 Z" fill="#d9822b"/>
    @endif
</svg>
