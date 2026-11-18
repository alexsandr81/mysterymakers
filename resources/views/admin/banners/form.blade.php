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
            <strong id="preview-title">Текущая картинка</strong>
            <img src="{{ $banner->image_url }}" alt="" class="banner-preview" id="banner-preview" data-original="{{ $banner->image_url }}">
        </div>
    @endunless

    @if($isNew)
        <label>Картинки: можно выбрать сразу до 5 файлов (JPG/PNG/WebP, до 5 МБ каждая, лучше 1600×500)
            <input type="file" name="images[]" id="banner-files" multiple required accept="image/jpeg,image/png,image/webp">
        </label>
        <div id="banner-previews" class="preview-row"></div>
        <p class="muted">Каждая картинка станет отдельным слайдом, они будут прокручиваться по кругу. Заголовок и ссылка (если заполнены ниже) применятся ко всем; потом их можно изменить у каждого баннера отдельно.</p>
    @else
        <label>Заменить картинку (JPG/PNG/WebP, до 5 МБ, лучше 1600×500)
            <input type="file" name="image" id="banner-file" accept="image/jpeg,image/png,image/webp">
        </label>
    @endif

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

<script>
(function () {
    // Сразу показываем выбранные файлы, не дожидаясь сохранения
    var single = document.getElementById('banner-file');
    if (single) {
        var img = document.getElementById('banner-preview');
        var title = document.getElementById('preview-title');
        single.addEventListener('change', function () {
            var f = single.files && single.files[0];
            if (f) {
                img.src = URL.createObjectURL(f);
                title.textContent = 'Новая картинка (будет сохранена после нажатия «Сохранить»)';
            } else {
                img.src = img.dataset.original;
                title.textContent = 'Текущая картинка';
            }
        });
    }
    var multi = document.getElementById('banner-files');
    if (multi) {
        var box = document.getElementById('banner-previews');
        multi.addEventListener('change', function () {
            box.innerHTML = '';
            var files = Array.prototype.slice.call(multi.files);
            if (files.length > 5) {
                alert('Можно выбрать не более 5 файлов. Лишние не будут загружены.');
                multi.value = '';
                return;
            }
            files.forEach(function (f) {
                var im = document.createElement('img');
                im.src = URL.createObjectURL(f);
                im.className = 'preview-thumb';
                box.appendChild(im);
            });
        });
    }
})();
</script>
@endsection
