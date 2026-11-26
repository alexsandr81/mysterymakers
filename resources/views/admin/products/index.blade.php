@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Товары')

@section('content')
<div class="admin-head">
    <h1>Товары</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn--accent">+ Добавить товар</a>
</div>

<p class="muted products-total">
    Всего товаров: <strong>{{ $totalAll }}</strong> · активных: <strong>{{ $activeAll }}</strong>@if($totalAll !== $activeAll) · скрытых: <strong>{{ $totalAll - $activeAll }}</strong>@endif
    @unless($grouped)· показано по фильтру: <strong>{{ $products->total() }}</strong>@endunless
</p>

@include('admin.partials.drive-hint')

<form method="GET" class="search search--admin">
    <input type="search" name="q" value="{{ $q }}" placeholder="Название или артикул">
    @if($onlyReview)<input type="hidden" name="review" value="1">@endif
    <button class="btn btn--brand">Найти</button>
</form>

@php
    $isAdmin = auth()->user()->is_admin;
    // Ссылка-заголовок: повторный клик меняет направление; поиск сохраняется
    $th = function (string $key, string $label) use ($sort, $dir, $q, $onlyReview) {
        $active = $sort === $key;
        $next = ($active && $dir === 'asc') ? 'desc' : 'asc';
        $arrow = $active ? ($dir === 'asc' ? '▲' : '▼') : '↕';
        $url = route('admin.products.index', array_filter(['q' => $q, 'review' => $onlyReview ? 1 : '', 'sort' => $key, 'dir' => $next], fn ($v) => $v !== ''));

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

@if($isAdmin)
<div class="review-filter">
    <a href="{{ route('admin.products.index', array_filter(['q' => $q])) }}" class="{{ $onlyReview ? '' : 'is-active' }}">Все товары</a>
    <a href="{{ route('admin.products.index', array_filter(['q' => $q, 'review' => 1])) }}" class="{{ $onlyReview ? 'is-active' : '' }}">⏳ Ждут проверки ({{ $reviewCount }})</a>
    @if($reviewCount)
        <button type="submit" form="bulk-form" formaction="{{ route('admin.products.bulk-reviewed') }}" formnovalidate data-review="1" class="link-btn" title="Снять метку «ждёт проверки» у товаров, отмеченных галочками слева">✔ Отметить выбранные проверенными</button>
    @endif
</div>
@endif

@include('admin.partials.col-picker', ['table' => 'products', 'cols' => array_filter(['photo' => 'Фото', 'sku' => 'Артикул', 'category' => 'Категория', 'sub' => 'Подкатегория', 'price' => 'Цена', 'stock' => 'Остаток', 'active' => 'Активен', 'review' => 'Проверка', 'created' => 'Добавлен'])])

@if($grouped)
    <p class="cat-bulk">
        <button type="button" class="btn btn--sm" id="grp-expand">▾ Развернуть все</button>
        <button type="button" class="btn btn--sm" id="grp-collapse">▸ Свернуть все</button>
        <a href="{{ route('admin.products.index', ['view' => 'list']) }}" class="btn btn--sm">☰ Списком</a>
    </p>
@elseif($q === '' && ! $onlyReview)
    <p class="cat-bulk"><a href="{{ route('admin.products.index') }}" class="btn btn--sm">🗂 По категориям</a></p>
@endif

<div class="table-wrap">
<table class="table table--compact" id="products-table" data-tools="products" data-default-hidden="created">
    <thead><tr><th data-col="chk" data-nores><input type="checkbox" id="bulk-toggle" title="Отметить всё на странице"></th><th data-col="photo" data-nores></th><th data-col="name">{{ $th('name', 'Название') }}</th><th data-col="sku">{{ $th('sku', 'Артикул') }}</th><th data-col="category">{{ $th('category', 'Категория') }}</th><th data-col="sub">{{ $th('sub', 'Подкатегория') }}</th><th data-col="price">{{ $th('price', 'Цена') }}</th><th data-col="stock">{{ $th('stock', 'Остаток') }}</th><th data-col="active">{{ $th('active', 'Актив.') }}</th><th data-col="review" data-nores>Проверка</th><th data-col="created">{{ $th('created', 'Добавлен') }}</th><th data-col="act" data-nores></th></tr></thead>
    <tbody>
    @if($grouped)
        @php $byCat = $products->getCollection()->groupBy('category_id'); @endphp
        @foreach($cats->whereNull('parent_id') as $top)
            @php
                $direct = $byCat->get($top->id, collect());
                $subs = $cats->where('parent_id', $top->id);
                $topAll = $direct->concat($subs->flatMap(fn ($sc) => $byCat->get($sc->id, collect())));
                $topReview = $topAll->where('needs_review', true)->count();
            @endphp
            @continue($topAll->isEmpty())
            <tr class="grp-row grp-row--top" data-ghead="{{ $top->id }}">
                <td colspan="12" data-fill>
                    <button type="button" class="cat-toggle" data-key="c{{ $top->id }}" aria-expanded="false" title="Показать или скрыть товары">▸</button>
                    <input type="checkbox" class="grp-check" data-scope="g" data-id="{{ $top->id }}" title="Отметить все товары этой категории (для массового изменения цены)">
                    📁 <strong>{{ $top->name_ru }}</strong>
                    <span class="badge badge--sm badge--top">{{ $topAll->count() }}</span>
                    @if($isAdmin && $topReview)<span class="badge badge--sm badge--review" title="Ждут проверки">⏳ {{ $topReview }}</span>@endif
                </td>
            </tr>
            @foreach($direct as $p)
                @include('admin.products._row', ['p' => $p, 'isAdmin' => $isAdmin, 'attrs' => 'hidden'])
            @endforeach
            @foreach($subs as $sub)
                @php $sp = $byCat->get($sub->id, collect()); @endphp
                @continue($sp->isEmpty())
                <tr class="grp-row grp-row--sub" data-g="{{ $top->id }}" data-shead="{{ $sub->id }}" hidden>
                    <td colspan="12" data-fill>
                        <span class="grp-indent"></span>
                        <button type="button" class="cat-toggle" data-key="s{{ $sub->id }}" aria-expanded="false" title="Показать или скрыть товары подкатегории">▸</button>
                        <input type="checkbox" class="grp-check" data-scope="s" data-id="{{ $sub->id }}" title="Отметить все товары этой подкатегории">
                        <span class="cat-name__arrow">↳</span> <strong>{{ $sub->name_ru }}</strong>
                        <span class="badge badge--sm badge--sub">{{ $sp->count() }}</span>
                    </td>
                </tr>
                @foreach($sp as $p)
                    @include('admin.products._row', ['p' => $p, 'isAdmin' => $isAdmin, 'attrs' => 'hidden'])
                @endforeach
            @endforeach
        @endforeach
    @else
    @foreach($products as $p)
        @include('admin.products._row', ['p' => $p, 'isAdmin' => $isAdmin, 'attrs' => ''])
    @endforeach
    @endif
    </tbody>
</table>
</div>
<script src="{{ asset('js/table-tools.js') }}"></script>
<script>
(function () {
    var form = document.getElementById('bulk-form');
    if (!form) return; // у модератора панели массовых действий нет
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
        if (e.submitter && e.submitter.dataset.review) { return; } // «Отметить проверенными» — не изменение цены
        var all = $('bulk-all').checked, n = checked().length;
        if (!all && n === 0) { e.preventDefault(); alert('Отметьте товары галочками слева.'); return; }
        if (!confirm(all ? 'Изменить цену у ВСЕХ товаров списка?' : 'Изменить цену у выбранных товаров (' + n + ')?')) e.preventDefault();
    });
    refresh();
})();

</script>
@if($grouped)
<script>
    // Товары по категориям: галочка рядом с названием раскрывает группу, что открыто — запоминается в браузере
    (function () {
        var table = document.getElementById('products-table'), KEY = 'mm_prod_groups', open = {};
        try { open = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
        function save() { try { localStorage.setItem(KEY, JSON.stringify(open)); } catch (e) {} }
        function paint() {
            table.querySelectorAll('tbody tr').forEach(function (tr) {
                var g = tr.dataset.g, s = tr.dataset.s;
                if (tr.dataset.ghead !== undefined) { tr.hidden = false; return; }
                if (tr.dataset.shead !== undefined) { tr.hidden = !open['c' + g]; return; }
                tr.hidden = !(open['c' + g] && (!s || open['s' + s]));
            });
            table.querySelectorAll('.cat-toggle[data-key]').forEach(function (b) {
                var o = !!open[b.dataset.key];
                b.setAttribute('aria-expanded', o ? 'true' : 'false');
                b.textContent = o ? '▾' : '▸';
            });
        }
        table.addEventListener('click', function (e) {
            var b = e.target.closest('.cat-toggle[data-key]');
            if (!b) return;
            open[b.dataset.key] = !open[b.dataset.key];
            save(); paint();
        });
        // Галочка группы отмечает все её товары (даже свёрнутые) — удобно для массового изменения цены
        table.addEventListener('change', function (e) {
            var c = e.target.closest('.grp-check');
            if (!c) return;
            var attr = c.dataset.scope === 's' ? 'data-s' : 'data-g';
            table.querySelectorAll('input[name="ids[]"][' + attr + '="' + c.dataset.id + '"]').forEach(function (b) { b.checked = c.checked; });
        });
        function all(state) {
            table.querySelectorAll('.cat-toggle[data-key]').forEach(function (b) { open[b.dataset.key] = state; });
            save(); paint();
        }
        document.getElementById('grp-expand').addEventListener('click', function () { all(true); });
        document.getElementById('grp-collapse').addEventListener('click', function () { all(false); });
        paint();
    })();
</script>
@endif
<div class="pager">{{ $products->links('partials.pagination') }}</div>
@endsection
