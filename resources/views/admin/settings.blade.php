@extends('admin.layout')

@section('title', 'Настройки')

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ \App\Support\Fonts::urlAll() }}">
@endpush

@section('content')
<h1>Настройки сайта</h1>

<form action="{{ route('admin.settings.update') }}" method="POST" class="form">
    @csrf @method('PUT')

    <h3>Название магазина</h3>
    <label>Название (в шапке, подвале, письмах и заголовке вкладки)
        <input type="text" name="site_name" value="{{ old('site_name', $values['site_name']) }}" required maxlength="60">
        <span class="muted">Например: Mystery Makers. Если в названии два слова, второе будет выделено цветом.</span>
    </label>
    <div class="form__cols">
        <label>Подпись под названием (укр.)
            <input type="text" name="tagline_uk" value="{{ old('tagline_uk', $values['tagline_uk']) }}" maxlength="120">
        </label>
        <label>Подпись под названием (рус.)
            <input type="text" name="tagline_ru" value="{{ old('tagline_ru', $values['tagline_ru']) }}" maxlength="120">
        </label>
    </div>

    <label>Описание магазина (укр.)
        <textarea name="description_uk" rows="3" maxlength="300">{{ old('description_uk', $values['description_uk']) }}</textarea>
    </label>
    <label>Описание магазина (рус.)
        <textarea name="description_ru" rows="3" maxlength="300">{{ old('description_ru', $values['description_ru']) }}</textarea>
        <span class="muted">Показывается на главной под названием и в поисковиках (до 300 символов).</span>
    </label>

    <h3>Шрифты</h3>
    <p class="muted">Можно подобрать шрифт для текста, заголовков и названия магазина. Все шрифты поддерживают украинский и русский. Предпросмотр ниже меняется сразу, на сайт шрифт попадёт после «Сохранить».</p>
    @php
        $fontField = function (string $name, string $label, bool $withSame, string $sameLabel = '') use ($values) {
            $cur = old($name, $values[$name]);
            $html = '<label>' . e($label) . '<select name="' . $name . '" data-font-select="' . $name . '">';
            if ($withSame) { $html .= '<option value="same"' . ($cur === 'same' ? ' selected' : '') . '>' . e($sameLabel) . '</option>'; }
            $html .= '<option value="system"' . ($cur === 'system' ? ' selected' : '') . '>Системный (по умолчанию)</option>';
            foreach (\App\Support\Fonts::GROUPS as $g => $gl) {
                $html .= '<optgroup label="' . e($gl) . '">';
                foreach (\App\Support\Fonts::LIST as $k => $f) {
                    if ($f[1] === $g) { $html .= '<option value="' . $k . '"' . ($cur === $k ? ' selected' : '') . '>' . e($f[0]) . '</option>'; }
                }
                $html .= '</optgroup>';
            }
            return new \Illuminate\Support\HtmlString($html . '</select></label>');
        };
    @endphp
    <div class="form__cols form__cols--3">
        {{ $fontField('font_body', 'Основной текст', false) }}
        {{ $fontField('font_heading', 'Заголовки', true, 'Как основной текст') }}
        {{ $fontField('font_logo', 'Название магазина (логотип)', true, 'Как заголовки') }}
    </div>
    @error('font_body')<span class="err">{{ $message }}</span>@enderror
    <div class="font-preview" id="font-preview">
        <div class="font-preview__logo" data-prev="logo">{{ $values['site_name'] }}</div>
        <div class="font-preview__head" data-prev="heading">Подарочные коробки из фанеры ручной работы</div>
        <p class="font-preview__text" data-prev="body">Подарункові коробки з фанери ручної роботи. Мастерская изделий из фанеры: коробки, брелки, чайные домики. 1234567890 грн.</p>
    </div>
    <p class="muted">Шрифты подгружаются с сервиса Google Fonts: посетителям нужен интернет, как и для любых других картинок и стилей. Если выбран «Системный», ничего не подгружается.</p>

    <h3>Контакты</h3>
    <p class="muted">Показываются в подвале сайта и в письмах клиентам.</p>
    <label>Телефоны (каждый с новой строки, до 5)
        <textarea name="phone" rows="3" required>{{ old('phone', $values['phone']) }}</textarea>
        <span class="muted">Кнопки Viber / WhatsApp / Telegram показываются для первого номера.</span>
        @error('phone')<span class="err">{{ $message }}</span>@enderror
    </label>
    <label>Email
        <input type="email" name="email" value="{{ old('email', $values['email']) }}">
    </label>
    <label>Email для уведомлений о новых заказах
        <input type="email" name="notify_email" value="{{ old('notify_email', $values['notify_email']) }}">
        <span class="muted">Сюда приходит письмо при каждом новом заказе. Если пусто — на Email магазина выше.</span>
    </label>
    <label>Адрес (необязательно)
        <input type="text" name="address" value="{{ old('address', $values['address']) }}">
    </label>
    <button class="btn btn--accent">Сохранить</button>
</form>

<script>
(function () {
    var map = @json(\App\Support\Fonts::previewMap());
    var sel = {};
    document.querySelectorAll('[data-font-select]').forEach(function (s) { sel[s.dataset.fontSelect] = s; });
    function resolve() {
        var body = sel.font_body.value;
        var head = sel.font_heading.value === 'same' ? body : sel.font_heading.value;
        var logo = sel.font_logo.value === 'same' ? head : sel.font_logo.value;
        return {body: body, heading: head, logo: logo};
    }
    function apply() {
        var r = resolve();
        document.querySelectorAll('[data-prev]').forEach(function (el) { el.style.fontFamily = map[r[el.dataset.prev]]; });
    }
    Object.keys(sel).forEach(function (k) { sel[k].addEventListener('change', apply); });
    apply();
})();
</script>
@endsection
