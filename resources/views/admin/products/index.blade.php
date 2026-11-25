@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Товары')

@section('content')
<div class="admin-head">
    <h1>Товары</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn--accent">+ Добавить товар</a>
</div>

@include('admin.partials.drive-hint')

<form method="GET" class="search search--admin">
    <input type="search" name="q" value="{{ $q }}" placeholder="Название или артикул">
    <button class="btn btn--brand">Найти</button>
</form>

@php
    // Ссылка-заголовок: повторный клик меняет направление; поиск сохраняется
    $th = function (string $key, string $label) use ($sort, $dir, $q) {
        $active = $sort === $key;
        $next = ($active && $dir === 'asc') ? 'desc' : 'asc';
        $arrow = $active ? ($dir === 'asc' ? '▲' : '▼') : '↕';
        $url = route('admin.products.index', array_filter(['q' => $q, 'sort' => $key, 'dir' => $next], fn ($v) => $v !== ''));

        return new \Illuminate\Support\HtmlString('<a class="sort ' . ($active ? 'is-active' : '') . '" href="' . e($url) . '">' . e($label) . ' <small>' . $arrow . '</small></a>');
    };
@endphp

@php $prices = $products->getCollection()->pluck('price')->map(fn ($v) => (float) $v)->unique()->sort()->values(); @endphp
<details class="bulk-wrap" @if($errors->has('bulk')) open @endif>
<summary>💰 Массовое изменение цены</summary>
<form action="{{ route('admin.products.bulk-price') }}" method="POST" id="bulk-form" class="bulk">
    @csrf
    <input type="hidden" name="q" value="{{ $q }}">
    <span class="muted">Выбрано: <b id="bulk-count">0</b></span>
    @if($prices->count() > 1)
        <label>Отметить с ценой
            <select id="bulk-by-price">
                <option value="">— выбрать —</option>
                @foreach($prices as $pr)
                    <option value="{{ $pr }}">{{ Money::format($pr) }}</option>
                @endforeach
            </select>
        </label>
    @endif
    <label>Действие
        <select name="mode" id="bulk-mode">
            <option value="percent_up">Увеличить на, %</option>
            <option value="percent_down">Уменьшить на, %</option>
            <option value="amount_up">Увеличить на, грн</option>
            <option value="amount_down">Уменьшить на, грн</option>
            <option value="set">Установить цену, грн</option>
        </select>
    </label>
    <label>Значение <input type="number" name="value" id="bulk-value" min="0.01" step="any" required></label>
    <label>Округлить до
        <select name="round" id="bulk-round">
            <option value="0">не округлять</option>
            <option value="1" selected>1 грн</option>
            <option value="5">5 грн</option>
            <option value="10">10 грн</option>
        </select>
    </label>
    <label class="bulk__all"><input type="checkbox" name="scope_all" value="1" id="bulk-all"> Ко всем {{ $products->total() }} товарам{{ $q !== '' ? ' из поиска' : '' }}, не только отмеченным</label>
    <button class="btn btn--brand" type="submit">Применить</button>
    <span class="muted" id="bulk-preview"></span>
    @error('bulk')<span class="text-warn">{{ $message }}</span>@enderror
</form>
</details>

<div class="colpick">
    <details>
        <summary>⚙ Столбцы</summary>
        <div class="colpick__list">
            @foreach(['photo' => 'Фото', 'sku' => 'Артикул', 'category' => 'Категория', 'sub' => 'Подкатегория', 'price' => 'Цена', 'stock' => 'Остаток', 'active' => 'Активен', 'created' => 'Добавлен'] as $key => $label)
                <label><input type="checkbox" data-colpick="{{ $key }}" checked> {{ $label }}</label>
            @endforeach
            <button type="button" class="link-btn" id="colpick-reset">Сбросить</button>
        </div>
    </details>
</div>

<table class="table table--compact" id="products-table">
    <thead><tr><th><input type="checkbox" id="bulk-toggle" title="Отметить всё на странице"></th><th data-col="photo"></th><th>{{ $th('name', 'Название') }}</th><th data-col="sku">{{ $th('sku', 'Артикул') }}</th><th data-col="category">{{ $th('category', 'Категория') }}</th><th data-col="sub">{{ $th('sub', 'Подкатегория') }}</th><th data-col="price">{{ $th('price', 'Цена') }}</th><th data-col="stock">{{ $th('stock', 'Остаток') }}</th><th data-col="active">{{ $th('active', 'Актив.') }}</th><th data-col="created">{{ $th('created', 'Добавлен') }}</th><th></th></tr></thead>
    <tbody>
    @foreach($products as $p)
        <tr>
            <td><input type="checkbox" name="ids[]" form="bulk-form" value="{{ $p->id }}" data-price="{{ (float) $p->price }}"></td>
            <td class="thumb" data-col="photo"><img src="{{ $p->main_image }}" alt=""></td>
            <td><a href="{{ route('product', $p) }}" target="_blank" title="Открыть страницу товара на сайте">{{ $p->name_ru }}</a></td>
            <td data-col="sku"><a href="{{ route('product', $p) }}" target="_blank" title="Открыть страницу товара на сайте">{{ $p->sku }}</a></td>
            @php($cat = $p->category)
            @php($top = $cat->parent ?? $cat)
            <td data-col="category"><span class="badge badge--sm badge--top">{{ $top->name_ru }}</span></td>
            <td data-col="sub">@if($cat->parent_id)<span class="badge badge--sm badge--sub">{{ $cat->name_ru }}</span>@else<span class="muted" title="Товар лежит прямо в категории, без подкатегории">—</span>@endif</td>
            <td data-col="price" class="nowrap">{{ Money::format($p->price) }}</td>
            <td data-col="stock" class="{{ $p->stock < 5 ? 'text-warn' : '' }}">{{ $p->stock }}</td>
            <td data-col="active">{{ $p->is_active ? '✓' : '—' }}</td>
            <td data-col="created" class="muted nowrap">{{ $p->created_at?->format('d.m.Y') }}</td>
            <td class="actions">
                @if($p->drive_folder_id)
                    <a href="{{ \App\Services\GoogleDrive::url($p->drive_folder_id) }}" target="_blank" title="Папка на Google Диске">📁</a>
                @elseif(\App\Services\GoogleDrive::enabled())
                    <form action="{{ route('admin.products.drive', $p) }}" method="POST">@csrf<button class="link-btn" title="Создать папку на Google Диске">📁+</button></form>
                @endif
                <a href="{{ route('admin.products.edit', $p) }}" title="Изменить">✎</a>
                <form action="{{ route('admin.products.destroy', $p) }}" method="POST" onsubmit="return confirm('Удалить товар?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger" title="Удалить">✕</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<script>
(function () {
    var form = document.getElementById('bulk-form');
    var boxes = function () { return Array.prototype.slice.call(document.querySelectorAll('input[name="ids[]"]')); };
    var checked = function () { return boxes().filter(function (b) { return b.checked; }); };
    var $ = function (id) { return document.getElementById(id); };
    var fmt = function (v) { return v.toLocaleString('ru-RU', {minimumFractionDigits: 2, maximumFractionDigits: 2}); };

    function newPrice(p) {
        var v = parseFloat(String($('bulk-value').value).replace(',', '.')) || 0, m = $('bulk-mode').value, step = parseInt($('bulk-round').value, 10);
        var r = m === 'percent_up' ? p * (1 + v / 100) : m === 'percent_down' ? p * (1 - v / 100) : m === 'amount_up' ? p + v : m === 'amount_down' ? p - v : v;
        if (step > 0) r = Math.round(r / step) * step;
        return Math.round(r * 100) / 100;
    }
    function refresh() {
        var c = checked();
        $('bulk-count').textContent = c.length;
        var v = parseFloat(String($('bulk-value').value).replace(',', '.')) || 0;
        $('bulk-preview').textContent = (c.length && v > 0) ? 'Пример: ' + fmt(parseFloat(c[0].dataset.price)) + ' → ' + fmt(newPrice(parseFloat(c[0].dataset.price))) + ' грн' : '';
    }
    document.addEventListener('change', function (e) {
        if (e.target.id === 'bulk-toggle') { boxes().forEach(function (b) { b.checked = e.target.checked; }); }
        if (e.target.id === 'bulk-by-price') {
            var val = parseFloat(e.target.value);
            boxes().forEach(function (b) { b.checked = !isNaN(val) && parseFloat(b.dataset.price) === val; });
        }
        refresh();
    });
    ['bulk-value', 'bulk-mode', 'bulk-round'].forEach(function (id) { $(id).addEventListener('input', refresh); });
    form.addEventListener('submit', function (e) {
        var all = $('bulk-all').checked, n = checked().length;
        if (!all && n === 0) { e.preventDefault(); alert('Отметьте товары галочками слева.'); return; }
        if (!confirm(all ? 'Изменить цену у ВСЕХ товаров списка?' : 'Изменить цену у выбранных товаров (' + n + ')?')) e.preventDefault();
    });
    refresh();
})();

// Выбор видимых столбцов: запоминается в браузере (отдельно у каждого пользователя)
(function () {
    var KEY = 'mm_products_cols', DEFAULT_HIDDEN = ['created'];
    var table = document.getElementById('products-table');
    var picks = Array.prototype.slice.call(document.querySelectorAll('[data-colpick]'));
    function load() {
        try { var v = JSON.parse(localStorage.getItem(KEY)); if (Array.isArray(v)) return v; } catch (e) {}
        return DEFAULT_HIDDEN.slice();
    }
    function save(hidden) { try { localStorage.setItem(KEY, JSON.stringify(hidden)); } catch (e) {} }
    function apply(hidden) {
        picks.forEach(function (c) { c.checked = hidden.indexOf(c.dataset.colpick) === -1; });
        Array.prototype.forEach.call(table.querySelectorAll('[data-col]'), function (el) {
            el.hidden = hidden.indexOf(el.dataset.col) !== -1;
        });
    }
    picks.forEach(function (c) {
        c.addEventListener('change', function () {
            var hidden = picks.filter(function (x) { return !x.checked; }).map(function (x) { return x.dataset.colpick; });
            save(hidden); apply(hidden);
        });
    });
    document.getElementById('colpick-reset').addEventListener('click', function () { save(DEFAULT_HIDDEN.slice()); apply(DEFAULT_HIDDEN.slice()); });
    apply(load());
})();
</script>
<div class="pager">{{ $products->links('partials.pagination') }}</div>
@endsection
