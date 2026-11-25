@extends('admin.layout')

@section('title', 'Характеристики')

@section('content')
<div class="admin-head">
    <h1>Характеристики товаров</h1>
    <a href="{{ route('admin.characteristics.create') }}" class="btn btn--accent">+ Добавить характеристику</a>
</div>

<p class="muted">Здесь задаются характеристики (материал, цвет, размер…) и их варианты. Выбираются они в карточке товара, а в каталоге по ним работают фильтры.</p>

<table class="table">
    <thead><tr><th>Название (рус.)</th><th>Значения</th><th>Фильтр в каталоге</th><th>Порядок</th><th></th></tr></thead>
    <tbody>
    @forelse($characteristics as $c)
        <tr>
            <td>{{ $c->name_ru }}</td>
            <td>{{ $c->values->pluck('value_ru')->implode(', ') ?: '—' }}</td>
            <td>{{ $c->is_filterable ? '✓' : '—' }}</td>
            <td>{{ $c->sort }}</td>
            <td class="actions">
                <a href="{{ route('admin.characteristics.edit', $c) }}">Изменить</a>
                @if(auth()->user()->is_admin)<form action="{{ route('admin.characteristics.destroy', $c) }}" method="POST" onsubmit="return confirm('Удалить характеристику вместе со всеми значениями у товаров?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="muted">Характеристик пока нет</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
