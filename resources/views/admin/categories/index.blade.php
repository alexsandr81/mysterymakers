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

<table class="table">
    <thead><tr><th>{{ $th('name', 'Название (рус.)') }}</th><th>{{ $th('name_uk', 'Название (укр.)') }}</th><th>{{ $th('parent', 'Родитель') }}</th><th>{{ $th('products', 'Товаров') }}</th><th>{{ $th('sku', 'Артикулы с') }}</th><th>{{ $th('sort', 'Порядок на сайте') }}</th><th></th></tr></thead>
    <tbody>
    @php
        // «Соседи» для кнопок ▲▼: первая и последняя категория своего уровня
        $firstIds = $categories->groupBy('parent_id')->map(fn ($g) => $g->first()->id);
        $lastIds = $categories->groupBy('parent_id')->map(fn ($g) => $g->last()->id);
    @endphp
    @foreach($categories as $c)
        <tr>
            <td>{{ $c->parent ? '— ' : '' }}{{ $c->name_ru }}</td>
            <td>{{ $c->name_uk }}</td>
            <td>{{ $c->parent?->name_ru ?? '—' }}</td>
            <td>{{ $c->products_count }}</td>
            <td class="muted">{{ $c->sku_start }}</td>
            <td>
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
                <a href="{{ route('admin.categories.edit', $c) }}">Изменить</a>
                <form action="{{ route('admin.categories.destroy', $c) }}" method="POST" onsubmit="return confirm('Удалить категорию?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
