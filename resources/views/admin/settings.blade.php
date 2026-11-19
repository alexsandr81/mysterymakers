@extends('admin.layout')

@section('title', 'Настройки')

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
@endsection
