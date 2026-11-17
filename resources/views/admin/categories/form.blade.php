@extends('admin.layout')

@php $isNew = !$category->exists; @endphp

@section('title', $isNew ? 'Новая категория' : 'Редактирование категории')

@section('content')
<h1>{{ $isNew ? 'Новая категория' : 'Редактирование категории' }}</h1>

<form action="{{ $isNew ? route('admin.categories.store') : route('admin.categories.update', $category) }}" method="POST" class="form">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <label>Название (укр.)
        <input type="text" name="name_uk" value="{{ old('name_uk', $category->name_uk) }}" required>
    </label>
    <label>Название (рус.)
        <input type="text" name="name_ru" value="{{ old('name_ru', $category->name_ru) }}" required>
    </label>
    <label>Родительская категория
        <select name="parent_id">
            <option value="">— нет (верхний уровень) —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" @selected(old('parent_id', $category->parent_id) == $p->id)>{{ $p->name_ru }}</option>
            @endforeach
        </select>
    </label>
    <label>Порядок сортировки
        <input type="number" min="0" name="sort" value="{{ old('sort', $category->sort) }}" required>
    </label>

    <div>
        <button type="submit" class="btn btn--accent">Сохранить</button>
        <a href="{{ route('admin.categories.index') }}" class="btn">Отмена</a>
    </div>
</form>
@endsection
