@extends('admin.layout')

@section('title', 'Категории')

@section('content')
<div class="admin-head">
    <h1>Категории</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn--accent">+ Добавить категорию</a>
</div>

@include('admin.partials.drive-hint')

@php
    // Ссылка-заголовок: повторный клик меняет направление (так же, как в товарах)
    $th = function (string $key, string $label) use ($sort, $dir) {
        $active = $sort === $key;
        $next = ($active && $dir === 'asc') ? 'desc' : 'asc';
        $arrow = $active ? ($dir === 'asc' ? '▲' : '▼') : '↕';
        $url = route('admin.categories.index', ['sort' => $key, 'dir' => $next]);

        return new \Illuminate\Support\HtmlString('<a class="sort ' . ($active ? 'is-active' : '') . '" href="' . e($url) . '">' . e($label) . ' <small>' . $arrow . '</small></a>');
    };
@endphp
@if($sort)
    <p class="muted">Список отсортирован по колонке. <a href="{{ route('admin.categories.index') }}">Показать деревом</a> (в нём работают кнопки ▲▼ порядка на сайте).</p>
@endif

@include('admin.partials.col-picker', ['table' => 'categories', 'cols' => ['type' => 'Тип', 'name_uk' => 'Название (укр.)', 'parent' => 'Родитель', 'products' => 'Товаров', 'sku' => 'Артикулы с', 'sort' => 'Порядок на сайте']])

@if(! $sort && $categories->whereNotNull('parent_id')->isNotEmpty())
    <p class="cat-bulk">
        <button type="button" class="btn btn--sm" id="cats-expand">▾ Развернуть все</button>
        <button type="button" class="btn btn--sm" id="cats-collapse">▸ Свернуть все</button>
    </p>
@endif
<div class="table-wrap">
<table class="table table--compact" data-tools="categories">
    <thead><tr><th data-col="type">Тип</th><th data-col="name">{{ $th('name', 'Название (рус.)') }}</th><th data-col="name_uk">{{ $th('name_uk', 'Название (укр.)') }}</th><th data-col="parent">{{ $th('parent', 'Родитель') }}</th><th data-col="products">{{ $th('products', 'Товаров') }}</th><th data-col="sku">{{ $th('sku', 'Артикулы с') }}</th><th data-col="sort">{{ $th('sort', 'Порядок на сайте') }}</th><th data-col="act" data-nores></th></tr></thead>
    <tbody>
    @php
        // «Соседи» для кнопок ▲▼: первая и последняя категория своего уровня
        $firstIds = $categories->groupBy('parent_id')->map(fn ($g) => $g->first()->id);
        $lastIds = $categories->groupBy('parent_id')->map(fn ($g) => $g->last()->id);
    @endphp
    @foreach($categories as $c)
        <tr class="{{ $c->parent_id ? 'cat-row cat-row--sub' : 'cat-row cat-row--top' }}" @if($c->parent_id && ! $sort) data-parent="{{ $c->parent_id }}" @endif>
            <td data-col="type">
                @if($c->parent_id)
                    <span class="badge badge--sm badge--sub">Подкатегория</span>
                @else
                    <span class="badge badge--sm badge--top">Категория</span>
                @endif
            </td>
            <td class="cat-name" data-col="name">
                @if($c->parent_id)
                    <span class="cat-name__arrow">↳</span>{{ $c->name_ru }}
                @else
                    @if(! $sort)
                        @if($c->children_count)
                            <button type="button" class="cat-toggle" data-id="{{ $c->id }}" aria-expanded="false" title="Показать или скрыть подкатегории">▸</button>
                        @else
                            <span class="cat-toggle cat-toggle--empty"></span>
                        @endif
                    @endif
                    @if($c->drive_folder_id)<a href="{{ \App\Services\GoogleDrive::url($c->drive_folder_id) }}" target="_blank" rel="noopener" class="drive-link" title="Открыть папку на Google Диске">📁 <strong>{{ $c->name_ru }}</strong></a>@else📁 <strong>{{ $c->name_ru }}</strong>@endif
                    <span class="muted">{{ $c->children_count ? '· подкатегорий: ' . $c->children_count : '' }}</span>
                @endif
            </td>
            <td data-col="name_uk">{{ $c->name_uk }}</td>
            <td data-col="parent">{{ $c->parent?->name_ru ?? '—' }}</td>
            <td data-col="products">{{ $c->products_count }}</td>
            <td data-col="sku" class="muted">{{ $c->sku_start }}</td>
            <td data-col="sort">
                @if($sort)
                    {{ $c->sort }}
                @else
                <span class="move-btns">
                    <form action="{{ route('admin.categories.move', [$c, 'up']) }}" method="POST">@csrf<button title="Выше" @disabled($firstIds[$c->parent_id ?? ''] === $c->id)>▲</button></form>
                    <form action="{{ route('admin.categories.move', [$c, 'down']) }}" method="POST">@csrf<button title="Ниже" @disabled($lastIds[$c->parent_id ?? ''] === $c->id)>▼</button></form>
                </span>
                @endif
            </td>
            <td class="actions">
                @if($c->drive_folder_id)
                    <a href="{{ \App\Services\GoogleDrive::url($c->drive_folder_id) }}" target="_blank" title="Папка на Google Диске">📁</a>
                @elseif(\App\Services\GoogleDrive::enabled())
                    <form action="{{ route('admin.categories.drive', $c) }}" method="POST">@csrf<button class="link-btn" title="Создать папку на Google Диске">📁+</button></form>
                @endif
                @unless($c->parent_id)
                    <a href="{{ route('admin.categories.create', ['parent_id' => $c->id]) }}" title="Добавить подкатегорию в «{{ $c->name_ru }}»">＋ Подкат.</a>
                @endunless
                <a href="{{ route('admin.categories.edit', $c) }}" title="Изменить">✎</a>
                <form action="{{ route('admin.categories.destroy', $c) }}" method="POST" onsubmit="return confirm('Удалить категорию?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger" title="Удалить">✕</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
<script src="{{ asset('js/table-tools.js') }}"></script>
@unless($sort)
<script>
    // Подкатегории раскрываются галочкой рядом с категорией; что открыто — запоминается в браузере
    (function () {
        var table = document.querySelector('table[data-tools=categories]'), KEY = 'mm_cats_open', open = {};
        try { open = JSON.parse(localStorage.getItem(KEY) || '{}') || {}; } catch (e) {}
        @if(session('open_category')) open[{{ (int) session('open_category') }}] = true; @endif
        function save() { try { localStorage.setItem(KEY, JSON.stringify(open)); } catch (e) {} }
        function paint() {
            table.querySelectorAll('tr[data-parent]').forEach(function (r) { r.hidden = !open[r.dataset.parent]; });
            table.querySelectorAll('.cat-toggle[data-id]').forEach(function (b) {
                var o = !!open[b.dataset.id];
                b.setAttribute('aria-expanded', o ? 'true' : 'false');
                b.textContent = o ? '▾' : '▸';
            });
        }
        table.addEventListener('click', function (e) {
            var b = e.target.closest('.cat-toggle[data-id]');
            if (!b) return;
            open[b.dataset.id] = !open[b.dataset.id];
            save(); paint();
        });
        function all(state) {
            table.querySelectorAll('.cat-toggle[data-id]').forEach(function (b) { open[b.dataset.id] = state; });
            save(); paint();
        }
        var ex = document.getElementById('cats-expand'), co = document.getElementById('cats-collapse');
        if (ex) ex.addEventListener('click', function () { all(true); });
        if (co) co.addEventListener('click', function () { all(false); });
        save(); paint();
    })();
</script>
@endunless
@endsection
