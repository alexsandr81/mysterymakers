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

<table class="table">
    <thead><tr><th></th><th>{{ $th('name', 'Название') }}</th><th>{{ $th('sku', 'Артикул') }}</th><th>{{ $th('category', 'Категория') }}</th><th>{{ $th('price', 'Цена') }}</th><th>{{ $th('stock', 'Остаток') }}</th><th>{{ $th('active', 'Активен') }}</th><th>{{ $th('created', 'Добавлен') }}</th><th></th></tr></thead>
    <tbody>
    @foreach($products as $p)
        <tr>
            <td class="thumb"><img src="{{ $p->main_image }}" alt=""></td>
            <td><a href="{{ route('product', $p) }}" target="_blank" title="Открыть страницу товара на сайте">{{ $p->name_ru }}</a></td>
            <td><a href="{{ route('product', $p) }}" target="_blank" title="Открыть страницу товара на сайте">{{ $p->sku }}</a></td>
            <td>{{ $p->category->name_ru }}</td>
            <td>{{ Money::format($p->price) }}</td>
            <td class="{{ $p->stock < 5 ? 'text-warn' : '' }}">{{ $p->stock }}</td>
            <td>{{ $p->is_active ? '✓' : '—' }}</td>
            <td class="muted">{{ $p->created_at?->format('d.m.Y') }}</td>
            <td class="actions">
                @if($p->drive_folder_id)
                    <a href="{{ \App\Services\GoogleDrive::url($p->drive_folder_id) }}" target="_blank" title="Папка на Google Диске">📁</a>
                @elseif(\App\Services\GoogleDrive::enabled())
                    <form action="{{ route('admin.products.drive', $p) }}" method="POST">@csrf<button class="link-btn" title="Создать папку на Google Диске">📁+</button></form>
                @endif
                <a href="{{ route('admin.products.edit', $p) }}">Изменить</a>
                <form action="{{ route('admin.products.destroy', $p) }}" method="POST" onsubmit="return confirm('Удалить товар?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
<div class="pager">{{ $products->links('partials.pagination') }}</div>
@endsection
