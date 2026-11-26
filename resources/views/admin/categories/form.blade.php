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
    <label>Артикулы начинаются с
        <input type="number" min="1" name="sku_start" value="{{ old('sku_start', $category->sku_start) }}" placeholder="Автоматически">
        <span class="muted">Обычно оставьте пустым. Категория верхнего уровня получает блок 10000–19999, 20000–29999…; её товары — первые 1000 номеров блока (10000–10999), а подкатегории — следующие тысячи внутри блока (11000–11999, 12000–12999…). Новые товары получают артикулы по порядку.</span>
        @if($category->exists)<span class="muted"><strong>Если изменить это число</strong>, на столько же сдвинутся начало артикулов подкатегорий и артикулы товаров этой категории (и подкатегорий), а папки товаров на Google Диске переименуются. Если новый диапазон занят, сохранить не получится.</span>@endif
        @error('sku_start')<span class="text-warn">{{ $message }}</span>@enderror
    </label>
    <label>Порядок сортировки
        <input type="number" min="0" name="sort" value="{{ old('sort', $category->sort) }}" required>
    </label>

    <div>
        <button type="submit" class="btn btn--accent">Сохранить</button>
        <a href="{{ route('admin.categories.index') }}" class="btn">Отмена</a>
    </div>
</form>
@if($category->exists)
<script>
    (function () {
        var input = document.querySelector('input[name=sku_start]'), form = input.closest('form'), old = String(input.value || '');
        form.addEventListener('submit', function (e) {
            if (input.value && old && input.value !== old && !confirm('Начало артикулов изменено: ' + old + ' → ' + input.value + '.\nАртикулы подкатегорий и товаров этой категории сдвинутся на столько же, папки на Google Диске переименуются.\nПродолжить?')) {
                e.preventDefault();
            }
        });
    })();
</script>
@endif
@endsection
