@extends('admin.layout')

@php use App\Support\Money; @endphp

@section('title', 'Товары')

@section('content')
<div class="admin-head">
    <h1>Товары</h1>
    <a href="{{ route('admin.products.create') }}" class="btn btn--accent">+ Добавить товар</a>
</div>

<form method="GET" class="search search--admin">
    <input type="search" name="q" value="{{ $q }}" placeholder="Название или артикул">
    <button class="btn btn--brand">Найти</button>
</form>

<table class="table">
    <thead><tr><th></th><th>Название</th><th>Артикул</th><th>Категория</th><th>Цена</th><th>Остаток</th><th>Активен</th><th></th></tr></thead>
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
            <td class="actions">
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
