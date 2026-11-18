@extends('admin.layout')

@php $isNew = !$banner->exists; @endphp

@section('title', $isNew ? 'Новый баннер' : 'Редактирование баннера')

@section('content')
<h1>{{ $isNew ? 'Новый баннер' : 'Редактирование баннера' }}</h1>

<form action="{{ $isNew ? route('admin.banners.store') : route('admin.banners.update', $banner) }}"
      method="POST" enctype="multipart/form-data" class="form form--wide">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    @unless($isNew)
        <div>
            <strong>Текущая картинка</strong>
            <img src="{{ $banner->image_url }}" alt="" class="banner-preview">
        </div>
    @endunless

    <label>{{ $isNew ? 'Картинка' : 'Заменить картинку' }} (JPG/PNG/WebP, до 5 МБ, лучше 1600×500)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" @required($isNew)>
    </label>

    <div class="form__cols">
        <label>Заголовок (укр.)
            <input type="text" name="title_uk" value="{{ old('title_uk', $banner->title_uk) }}">
        </label>
        <label>Заголовок (рус.)
            <input type="text" name="title_ru" value="{{ old('title_ru', $banner->title_ru) }}">
        </label>
    </div>

    <div class="form__cols">
        <label>Подзаголовок (укр.)
            <input type="text" name="subtitle_uk" value="{{ old('subtitle_uk', $banner->subtitle_uk) }}">
        </label>
        <label>Подзаголовок (рус.)
            <input type="text" name="subtitle_ru" value="{{ old('subtitle_ru', $banner->subtitle_ru) }}">
        </label>
    </div>

    <label>Ссылка при нажатии (необязательно)
        <input type="text" name="url" value="{{ old('url', $banner->url) }}" placeholder="/catalog/minibary или https://...">
    </label>

    <div class="form__cols form__cols--4">
        <label>Порядок
            <input type="number" min="0" name="sort" value="{{ old('sort', $banner->sort) }}" required>
        </label>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $banner->is_active))> Показывать на сайте
        </label>
    </div>

    <div>
        <button type="submit" class="btn btn--accent btn--lg">Сохранить</button>
        <a href="{{ route('admin.banners.index') }}" class="btn">Отмена</a>
    </div>
</form>
@endsection
