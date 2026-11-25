@extends('admin.layout')

@php $isNew = !$holiday->exists; @endphp

@section('title', $isNew ? 'Новый праздник' : 'Редактирование праздника')

@section('content')
<h1>{{ $isNew ? 'Новый праздник' : 'Редактирование праздника' }}</h1>

<form action="{{ $isNew ? route('admin.holidays.store') : route('admin.holidays.update', $holiday) }}" method="POST" class="form">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <label>Название (укр.)
        <input type="text" name="name_uk" value="{{ old('name_uk', $holiday->name_uk) }}" required>
    </label>
    <label>Название (рус.)
        <input type="text" name="name_ru" value="{{ old('name_ru', $holiday->name_ru) }}" required>
    </label>
    <label>Порядок в фильтре
        <input type="number" min="0" name="sort" value="{{ old('sort', $holiday->sort) }}" required>
    </label>
    <label class="check">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $holiday->is_active))> Показывать в фильтре каталога
    </label>

    <div>
        <button type="submit" class="btn btn--accent">Сохранить</button>
        <a href="{{ route('admin.holidays.index') }}" class="btn">Отмена</a>
    </div>
</form>
@endsection
