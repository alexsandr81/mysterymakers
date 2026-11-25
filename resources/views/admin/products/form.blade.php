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

    @php
        // Категория и подкатегория выбираются отдельно. Значения: id | new (создать новую) | self (товар прямо в категории)
        $tops = $categories->whereNull('parent_id')->values();
        $kids = $categories->whereNotNull('parent_id')->groupBy('parent_id');
        $curId = (int) old('category_id', $product->category_id);
        $cur = $categories->firstWhere('id', $curId);
        $topSel = old('category_top', $cur ? ($cur->parent_id ?: $cur->id) : ($tops->first()->id ?? 'new'));
        $subRaw = old('category_id');
        $subSel = in_array($subRaw, ['self', 'new'], true) ? $subRaw : (($cur && $cur->parent_id) ? (string) $cur->id : 'self');
        $kidsJs = $kids->map(fn ($g) => $g->map(fn ($c) => ['id' => $c->id, 'name' => $c->name_ru])->values())->all();
    @endphp
    <div class="form__stack">
        <label>Категория
            <select name="category_top" id="cat-top" required>
                @foreach($tops as $c)
                    <option value="{{ $c->id }}" @selected((string) $topSel === (string) $c->id)>{{ $c->name_ru }}</option>
                @endforeach
                <option value="new" @selected($topSel === 'new')>＋ Новая категория…</option>
            </select>
        </label>
        <label id="cat-sub-wrap"><span>Подкатегория <span class="muted">(необязательно)</span></span>
            <select name="category_id" id="cat-sub" required>
                <option value="self" @selected($subSel === 'self')>— без подкатегории (товар прямо в категории) —</option>
                @foreach(($topSel === 'new' ? collect() : ($kids[(int) $topSel] ?? collect())) as $c)
                    <option value="{{ $c->id }}" @selected($subSel === (string) $c->id)>{{ $c->name_ru }}</option>
                @endforeach
                <option value="new" @selected($subSel === 'new')>＋ Новая подкатегория…</option>
            </select>
        </label>
    </div>
    <div class="form__cols" id="new-cat" hidden>
        <label>Новая категория (укр.) <input type="text" name="new_cat_uk" value="{{ old('new_cat_uk') }}" maxlength="255"></label>
        <label>Новая категория (рус.) <input type="text" name="new_cat_ru" value="{{ old('new_cat_ru') }}" maxlength="255"></label>
    </div>
    <div class="form__cols" id="new-sub" hidden>
        <label>Новая подкатегория (укр.) <input type="text" name="new_sub_uk" value="{{ old('new_sub_uk') }}" maxlength="255"></label>
        <label>Новая подкатегория (рус.) <input type="text" name="new_sub_ru" value="{{ old('new_sub_ru') }}" maxlength="255"></label>
    </div>
    @if($errors->hasAny(['category_top', 'category_id', 'new_cat_uk', 'new_cat_ru', 'new_sub_uk', 'new_sub_ru']))
        <p class="text-warn">{{ $errors->first('category_top') ?: ($errors->first('new_cat_uk') ?: ($errors->first('new_cat_ru') ?: ($errors->first('new_sub_uk') ?: ($errors->first('new_sub_ru') ?: $errors->first('category_id'))))) }}</p>
    @endif
    <script>
    (function () {
        var kids = @json($kidsJs), top = document.getElementById('cat-top'), sub = document.getElementById('cat-sub');
        var newCat = document.getElementById('new-cat'), newSub = document.getElementById('new-sub');
        function opt(value, text) { var o = document.createElement('option'); o.value = value; o.textContent = text; return o; }
        function toggleBox(box, on) {
            box.hidden = !on;
            box.querySelectorAll('input').forEach(function (i) { i.disabled = !on; i.required = on; });
        }
        function sync() {
            toggleBox(newCat, top.value === 'new');
            toggleBox(newSub, sub.value === 'new');
        }
        function fill() {
            var list = top.value === 'new' ? [] : (kids[top.value] || []);
            sub.innerHTML = '';
            sub.appendChild(opt('self', '— без подкатегории (товар прямо в категории) —'));
            list.forEach(function (c) { sub.appendChild(opt(c.id, c.name)); });
            sub.appendChild(opt('new', '＋ Новая подкатегория…'));
            sync();
        }
        top.addEventListener('change', fill);
        sub.addEventListener('change', sync);
        sync();
    })();
    </script>

    <div class="form__cols form__cols--narrow">
        <label>Цена, грн
            <input type="number" step="0.01" min="0.01" name="price" value="{{ old('price', $product->price > 0 ? $product->price : '') }}" @if(auth()->user()->is_admin) required @endif>
            @unless(auth()->user()->is_admin)<span class="muted">Можно оставить пустой: на сайте будет «Цена уточняется».</span>@endunless
        </label>
        <label>Старая цена (для скидки)
            <input type="number" step="0.01" name="old_price" value="{{ old('old_price', $product->old_price) }}">
        </label>
    </div>

    <div class="form__stack">
        <label>Артикул
            <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="Автоматически (по категории)">
            <span class="muted">Пусто — номер присвоится сам.</span>
        </label>
    </div>

    @php $shape = old('size_shape', $product->size_diameter ? 'round' : 'rect'); @endphp
    <div class="form__stack">
        <label>Размер изделия, мм
            <select name="size_shape" id="size-shape">
                <option value="rect" @selected($shape === 'rect')>Длина × ширина</option>
                <option value="round" @selected($shape === 'round')>Диаметр</option>
            </select>
        </label>
    </div>
    <div class="form__cols form__cols--3" id="size-fields">
        <label data-for="rect">Длина <input type="number" min="1" name="size_length" value="{{ old('size_length', $product->size_length) }}" placeholder="мм"></label>
        <label data-for="rect">Ширина <input type="number" min="1" name="size_width" value="{{ old('size_width', $product->size_width) }}" placeholder="мм"></label>
        <label data-for="round">Диаметр <input type="number" min="1" name="size_diameter" value="{{ old('size_diameter', $product->size_diameter) }}" placeholder="мм"></label>
        <label>Высота <input type="number" min="1" name="size_height" value="{{ old('size_height', $product->size_height) }}" placeholder="мм"></label>
    </div>
    <p class="muted">Только цифры — «мм» подставится автоматически. Для круглых изделий выберите «Диаметр»: поля длины и ширины скроются, на сайте будет «⌀ 120 мм, высота 50 мм».</p>
    <script>
    (function () {
        var sel = document.getElementById('size-shape'), box = document.getElementById('size-fields');
        function apply() {
            box.querySelectorAll('label[data-for]').forEach(function (l) {
                var off = l.dataset.for !== sel.value;
                l.hidden = off;                       // скрытое поле не видно и не отправляется
                l.querySelector('input').disabled = off;
            });
        }
        sel.addEventListener('change', apply);
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

    @if(! $isNew && $product->needs_review)
        <div class="review-box">
            ⏳ <strong>Ждёт проверки.</strong>
            @if($product->modifier)Последним менял: {{ $product->modifier->name }}.@endif
            @if(auth()->user()->is_admin)
                <label class="check"><input type="checkbox" name="reviewed" value="1" @checked(old('reviewed', true))> Проверено (снять метку при сохранении)</label>
            @else
                <span class="muted">После ваших изменений администратор ещё раз проверит товар.</span>
            @endif
        </div>
    @endif

    <div>
        <strong>Праздники и поводы</strong> <span class="muted">(можно выбрать несколько — по ним работает фильтр в каталоге)</span>
        @php $chosenHol = old('holidays') !== null ? array_map('intval', (array) old('holidays')) : $selectedHolidays; @endphp
        <div class="holiday-pick">
            @foreach($holidays as $h)
                <label class="check"><input type="checkbox" name="holidays[]" value="{{ $h->id }}" @checked(in_array($h->id, $chosenHol, true))> {{ $h->name_ru }}</label>
            @endforeach
            <button type="button" class="link-btn" id="new-holiday-btn">＋ Новый праздник</button>
        </div>
        <div class="form__cols" id="new-holiday" @if(!old('new_holiday_uk') && !old('new_holiday_ru')) hidden @endif>
            <label>Новый праздник (укр.) <input type="text" name="new_holiday_uk" value="{{ old('new_holiday_uk') }}" maxlength="255"></label>
            <label>Новый праздник (рус.) <input type="text" name="new_holiday_ru" value="{{ old('new_holiday_ru') }}" maxlength="255"></label>
        </div>
        @error('new_holiday_uk')<p class="text-warn">{{ $message }}</p>@enderror
        @error('new_holiday_ru')<p class="text-warn">{{ $message }}</p>@enderror
    </div>
    <script>
        document.getElementById('new-holiday-btn').addEventListener('click', function () {
            var box = document.getElementById('new-holiday');
            box.hidden = !box.hidden;
            if (!box.hidden) box.querySelector('input').focus();
        });
    </script>

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
