@extends('admin.layout')

@php $isNew = !$page->exists; @endphp

@section('title', $isNew ? 'Новая страница' : 'Редактирование страницы')

@section('content')
<h1>{{ $isNew ? 'Новая страница' : 'Редактирование страницы' }}</h1>

<form action="{{ $isNew ? route('admin.pages.store') : route('admin.pages.update', $page) }}" method="POST" class="form form--wide">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="form__cols">
        <label>Заголовок (укр.)
            <input type="text" name="title_uk" value="{{ old('title_uk', $page->title_uk) }}" required>
        </label>
        <label>Заголовок (рус.)
            <input type="text" name="title_ru" value="{{ old('title_ru', $page->title_ru) }}" required>
        </label>
    </div>

    <label>Адрес страницы (латиницей, например: delivery)
        <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" pattern="[A-Za-z0-9_\-]+" required>
        <span class="muted">Страница будет доступна по адресу /page/<b>адрес</b></span>
    </label>

    <div class="form__cols">
        <label>Текст (укр.)
            <textarea name="body_uk" rows="12">{{ old('body_uk', $page->body_uk) }}</textarea>
        </label>
        <label>Текст (рус.)
            <textarea name="body_ru" rows="12">{{ old('body_ru', $page->body_ru) }}</textarea>
        </label>
    </div>
    <p class="muted">Переводы строк сохраняются как есть. Если текст на одном языке пустой, показывается другой.</p>

    <div class="form__cols form__cols--4">
        <label>Порядок
            <input type="number" min="0" name="sort" value="{{ old('sort', $page->sort) }}" required>
        </label>
        <label class="check">
            <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $page->is_published))> Опубликована
        </label>
        <label class="check">
            <input type="checkbox" name="show_in_footer" value="1" @checked(old('show_in_footer', $page->show_in_footer))> Ссылка в подвале
        </label>
    </div>

    <div>
        <button type="submit" class="btn btn--accent btn--lg">Сохранить</button>
        <a href="{{ route('admin.pages.index') }}" class="btn">Отмена</a>
    </div>
</form>
@endsection
