@extends('admin.layout')

@section('title', 'Настройки')

@section('content')
<h1>Настройки сайта</h1>
<p class="muted">Эти данные показываются в подвале сайта (внизу каждой страницы).</p>

<form action="{{ route('admin.settings.update') }}" method="POST" class="form">
    @csrf @method('PUT')
    <label>Телефон
        <input type="text" name="phone" value="{{ old('phone', $values['phone']) }}" required>
    </label>
    <label>Email
        <input type="email" name="email" value="{{ old('email', $values['email']) }}">
    </label>
    <label>Адрес (необязательно)
        <input type="text" name="address" value="{{ old('address', $values['address']) }}">
    </label>
    <button class="btn btn--accent">Сохранить</button>
</form>
@endsection
