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
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" required>
        </label>
        <label>Цена, грн
            <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', $product->price) }}" required>
        </label>
        <label>Старая цена (для скидки)
            <input type="number" step="0.01" name="old_price" value="{{ old('old_price', $product->old_price) }}">
        </label>
    </div>

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
            <strong>Текущие фото</strong> (отметьте, чтобы удалить)
            <div class="thumbs-edit">
                @foreach($product->images as $path)
                    <label class="thumbs-edit__item">
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" alt="">
                        <span><input type="checkbox" name="remove_images[]" value="{{ $path }}"> удалить</span>
                    </label>
                @endforeach
            </div>
        </div>
    @endif

    <label>Новые фото (до 5, JPG/PNG/WebP, до 4 МБ)
        <input type="file" name="images[]" id="product-files" multiple accept="image/jpeg,image/png,image/webp">
    </label>

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
        Array.prototype.forEach.call(input.files, function (f) {
            var im = document.createElement('img');
            im.src = URL.createObjectURL(f);
            im.className = 'preview-thumb';
            box.appendChild(im);
        });
    });
})();
</script>
@endsection
