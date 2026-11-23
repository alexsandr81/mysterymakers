@extends('admin.layout')

@section('title', 'Категории')

@section('content')
<div class="admin-head">
    <h1>Категории</h1>
    <a href="{{ route('admin.categories.create') }}" class="btn btn--accent">+ Добавить категорию</a>
</div>

<table class="table">
    <thead><tr><th>Название (рус.)</th><th>Название (укр.)</th><th>Родитель</th><th>Товаров</th><th>Порядок на сайте</th><th></th></tr></thead>
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
            <td>
                <span class="move-btns">
                    <form action="{{ route('admin.categories.move', [$c, 'up']) }}" method="POST">@csrf<button title="Выше" @disabled($firstIds[$c->parent_id ?? ''] === $c->id)>▲</button></form>
                    <form action="{{ route('admin.categories.move', [$c, 'down']) }}" method="POST">@csrf<button title="Ниже" @disabled($lastIds[$c->parent_id ?? ''] === $c->id)>▼</button></form>
                </span>
            </td>
            <td class="actions">
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
