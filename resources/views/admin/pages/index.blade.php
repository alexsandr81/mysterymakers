@extends('admin.layout')

@section('title', 'Страницы')

@section('content')
<div class="admin-head">
    <h1>Страницы</h1>
    <a href="{{ route('admin.pages.create') }}" class="btn btn--accent">+ Добавить страницу</a>
</div>

<table class="table">
    <thead><tr><th>Заголовок (рус.)</th><th>Адрес</th><th>Опубликована</th><th>В подвале</th><th>Порядок</th><th></th></tr></thead>
    <tbody>
    @forelse($pages as $p)
        <tr>
            <td>{{ $p->title_ru }}</td>
            <td><a href="{{ route('page', $p) }}" target="_blank">/page/{{ $p->slug }}</a></td>
            <td>{{ $p->is_published ? '✓' : '—' }}</td>
            <td>{{ $p->show_in_footer ? '✓' : '—' }}</td>
            <td>{{ $p->sort }}</td>
            <td class="actions">
                <a href="{{ route('admin.pages.edit', $p) }}">Изменить</a>
                <form action="{{ route('admin.pages.destroy', $p) }}" method="POST" onsubmit="return confirm('Удалить страницу?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Страниц пока нет</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
