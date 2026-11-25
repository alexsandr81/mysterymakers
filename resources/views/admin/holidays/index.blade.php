@extends('admin.layout')

@section('title', 'Праздники')

@section('content')
<div class="admin-head">
    <h1>Праздники и поводы</h1>
    <a href="{{ route('admin.holidays.create') }}" class="btn btn--accent">+ Добавить праздник</a>
</div>

<p class="muted">Тематические метки товаров: Новый год, 8 Марта и так далее. Отмечаются в карточке товара (можно несколько), а в каталоге по ним работает фильтр «Праздник / повод». Скрытый праздник не показывается покупателям, но у товаров остаётся.</p>

<table class="table">
    <thead><tr><th>Название (рус.)</th><th>Название (укр.)</th><th>Товаров</th><th>Порядок</th><th>В фильтре</th><th></th></tr></thead>
    <tbody>
    @forelse($holidays as $h)
        <tr>
            <td>{{ $h->name_ru }}</td>
            <td>{{ $h->name_uk }}</td>
            <td>{{ $h->products_count }}</td>
            <td>{{ $h->sort }}</td>
            <td>{{ $h->is_active ? '✓' : 'скрыт' }}</td>
            <td class="actions">
                <a href="{{ route('admin.holidays.edit', $h) }}">Изменить</a>
                @if(auth()->user()->is_admin)<form action="{{ route('admin.holidays.destroy', $h) }}" method="POST" onsubmit="return confirm('Удалить праздник? У товаров эта метка пропадёт.')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>@endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Праздников пока нет</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
