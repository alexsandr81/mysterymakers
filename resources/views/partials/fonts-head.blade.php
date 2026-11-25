{{-- Шрифты сайта (админка → Настройки → Шрифты). Если выбран «Системный» — ничего не подгружается. --}}
@php
    $fontSel = \App\Support\Fonts::selected();
    $fontUrl = \App\Support\Fonts::url(array_values($fontSel));
@endphp
@if($fontUrl)
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ $fontUrl }}">
@endif
<style>{!! \App\Support\Fonts::css() !!}</style>
