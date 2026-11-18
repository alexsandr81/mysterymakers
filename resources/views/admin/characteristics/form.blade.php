@extends('admin.layout')

@php $isNew = !$characteristic->exists; @endphp

@section('title', $isNew ? 'Новая характеристика' : 'Редактирование характеристики')

@section('content')
<h1>{{ $isNew ? 'Новая характеристика' : 'Редактирование характеристики' }}</h1>

<form action="{{ $isNew ? route('admin.characteristics.store') : route('admin.characteristics.update', $characteristic) }}" method="POST" class="form">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <label>Название (укр.)
        <input type="text" name="name_uk" value="{{ old('name_uk', $characteristic->name_uk) }}" required>
    </label>
    <label>Название (рус.)
        <input type="text" name="name_ru" value="{{ old('name_ru', $characteristic->name_ru) }}" required>
    </label>

    <label>Значения — по одному в строке, формат «украинский | русский»
        <textarea name="values" rows="8" placeholder="Фанера | Фанера&#10;МДФ | МДФ&#10;Береза">{{ old('values', $valuesText) }}</textarea>
        <span class="muted">Если после «|» ничего нет, русское название будет таким же, как украинское. Переименуйте украинское название — и значение считается новым (у товаров оно сбросится).</span>
    </label>

    <label>Порядок
        <input type="number" min="0" name="sort" value="{{ old('sort', $characteristic->sort) }}" required>
    </label>
    <label class="check">
        <input type="checkbox" name="is_filterable" value="1" @checked(old('is_filterable', $characteristic->is_filterable))> Показывать фильтром в каталоге
    </label>

    <div>
        <button type="submit" class="btn btn--accent">Сохранить</button>
        <a href="{{ route('admin.characteristics.index') }}" class="btn">Отмена</a>
    </div>
</form>
@endsection
