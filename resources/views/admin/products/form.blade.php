@extends('admin.layout')

@php $isNew = !$product->exists; @endphp

@section('title', $isNew ? 'Новый товар' : 'Редактирование товара')

@section('content')
<h1>{{ $isNew ? 'Новый товар' : 'Редактирование товара' }}</h1>

<form action="{{ $isNew ? route('admin.products.store') : route('admin.products.update', $product) }}"
      method="POST" enctype="multipart/form-data" class="form form--wide">
    @csrf
    @unless($isNew) @method('PUT') @endunless

    <div class="form__cols">
        <label>Название (укр.)
            <input type="text" name="name_uk" value="{{ old('name_uk', $product->name_uk) }}" required>
        </label>
        <label>Название (рус.)
            <input type="text" name="name_ru" value="{{ old('name_ru', $product->name_ru) }}" required>
        </label>
    </div>

    <div class="form__cols">
        <label>Описание (укр.)
            <textarea name="description_uk" rows="5">{{ old('description_uk', $product->description_uk) }}</textarea>
        </label>
        <label>Описание (рус.)
            <textarea name="description_ru" rows="5">{{ old('description_ru', $product->description_ru) }}</textarea>
        </label>
    </div>

    <div class="form__cols form__cols--4">
        <label>Категория
            <select name="category_id" required>
                @foreach($categories as $c)
                    <option value="{{ $c->id }}" @selected(old('category_id', $product->category_id) == $c->id)>{{ $c->name_ru }}</option>
                @endforeach
            </select>
        </label>
        <label>Артикул
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Автоматически (по категории)">
            <span class="muted">Оставьте пустым — присвоится следующий номер категории.</span>
        </label>
        <label>Цена, грн
            <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', $product->price) }}" required>
        </label>
        <label>Старая цена (для скидки)
            <input type="number" step="0.01" name="old_price" value="{{ old('old_price', $product->old_price) }}">
        </label>
    </div>

    <h3>Размер изделия, мм</h3>
    @php $shape = old('size_shape', $product->size_diameter ? 'round' : 'rect'); @endphp
    <div class="seg" id="shape-tabs" role="radiogroup" aria-label="Форма изделия">
        <label class="seg__item"><input type="radio" name="size_shape" value="rect" @checked($shape === 'rect')> ▭ Длина × ширина</label>
        <label class="seg__item"><input type="radio" name="size_shape" value="round" @checked($shape === 'round')> ◯ Диаметр</label>
    </div>
    <div class="form__cols form__cols--4" id="size-fields">
        <label data-for="rect">Длина <input type="number" min="1" name="size_length" value="{{ old('size_length', $product->size_length) }}" placeholder="мм"></label>
        <label data-for="rect">Ширина <input type="number" min="1" name="size_width" value="{{ old('size_width', $product->size_width) }}" placeholder="мм"></label>
        <label data-for="round">Диаметр <input type="number" min="1" name="size_diameter" value="{{ old('size_diameter', $product->size_diameter) }}" placeholder="мм"></label>
        <label>Высота <input type="number" min="1" name="size_height" value="{{ old('size_height', $product->size_height) }}" placeholder="мм"></label>
    </div>
    <p class="muted">Только цифры — «мм» подставится автоматически. Для круглых изделий выберите «Диаметр»: длина и ширина отключатся, на сайте будет «⌀ 120 мм, высота 50 мм».</p>
    <script>
    (function () {
        var box = document.getElementById('size-fields');
        function apply() {
            var shape = document.querySelector('#shape-tabs input:checked').value;
            box.querySelectorAll('label[data-for]').forEach(function (l) {
                var off = l.dataset.for !== shape;
                l.classList.toggle('is-off', off);
                l.querySelector('input').disabled = off;
            });
        }
        document.querySelectorAll('#shape-tabs input').forEach(function (r) { r.addEventListener('change', apply); });
        apply();
    })();
    </script>

    <div class="form__cols form__cols--4">
        <label>Остаток
            <input type="number" min="0" name="stock" value="{{ old('stock', $product->stock) }}" required>
        </label>
        <label class="check">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $product->is_active))> Показывать на сайте
        </label>
    </div>

    @if($characteristics->isNotEmpty())
        @php
            // После ошибки валидации показываем то, что человек уже выбрал
            $chosen = old('char') !== null
                ? array_map('intval', array_filter((array) old('char'), 'is_numeric'))
                : $selectedValues;
        @endphp
        <div>
            <strong>Характеристики</strong>
            <div class="form__cols form__cols--4 specs-edit">
                @foreach($characteristics as $characteristic)
                    <label>{{ $characteristic->name_ru }}
                        <select name="char[{{ $characteristic->id }}]">
                            <option value="">— не выбрано —</option>
                            @foreach($characteristic->values as $value)
                                <option value="{{ $value->id }}" @selected(in_array($value->id, $chosen, true))>{{ $value->value_ru }}</option>
                            @endforeach
                        </select>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    @if(!$isNew && !empty($product->images))
        <div>
            <strong>Текущие фото</strong> (выберите главное — оно будет первым в каталоге и на карточке; отметьте, чтобы удалить)
            <div class="thumbs-edit">
                @foreach($product->images as $path)
                    <div class="thumbs-edit__item">
                        <img src="{{ \App\Support\ImageStore::url($path) }}" alt="">
                        <label><input type="radio" name="main_choice" value="{{ $path }}" @checked(old('main_choice', $product->images[0]) === $path)> главное</label>
                        <label><input type="checkbox" name="remove_images[]" value="{{ $path }}"> удалить</label>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <label>Новые фото (до 5, JPG/PNG/WebP, до 20 МБ)
        <input type="file" name="images[]" id="product-files" multiple accept="image/jpeg,image/png,image/webp">
    </label>
    <p class="muted" id="main-hint">Главное фото (обложка) выбирается под превью: по умолчанию первое из загруженных. Оно показывается в каталоге и первым на карточке товара.</p>

    <div>
        <button type="submit" class="btn btn--accent btn--lg">Сохранить</button>
        <a href="{{ route('admin.products.index') }}" class="btn">Отмена</a>
    </div>
</form>
<script>
(function () {
    var input = document.getElementById('product-files');
    if (!input) return;
    var box = document.createElement('div');
    box.className = 'preview-row';
    input.closest('label').after(box);
    input.addEventListener('change', function () {
        box.innerHTML = '';
        var hasExisting = !!document.querySelector('input[name="main_choice"]:not([value^="new:"])');
        Array.prototype.forEach.call(input.files, function (f, i) {
            var item = document.createElement('label');
            item.className = 'preview-item';
            var im = document.createElement('img');
            im.src = URL.createObjectURL(f);
            im.className = 'preview-thumb';
            var r = document.createElement('input');
            r.type = 'radio';
            r.name = 'main_choice';
            r.value = 'new:' + i;
            r.checked = !hasExisting && i === 0;
            item.appendChild(im);
            item.appendChild(r);
            item.appendChild(document.createTextNode(' главное'));
            box.appendChild(item);
        });
    });
})();
</script>
@endsection
