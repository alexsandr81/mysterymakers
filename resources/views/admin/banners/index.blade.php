@extends('admin.layout')

@section('title', 'Баннеры')

@section('content')
<div class="admin-head">
    <h1>Баннеры на главной</h1>
    <a href="{{ route('admin.banners.create') }}" class="btn btn--accent">+ Добавить баннер</a>
</div>

<table class="table">
    <thead><tr><th>Картинка</th><th>Заголовок (рус.)</th><th>Ссылка</th><th>Порядок</th><th>Показывать</th><th></th></tr></thead>
    <tbody>
    @forelse($banners as $b)
        <tr>
            <td class="thumb thumb--wide"><img src="{{ $b->image_url }}" alt=""></td>
            <td>{{ $b->title_ru ?: '—' }}</td>
            <td>{{ $b->url ?: '—' }}</td>
            <td>{{ $b->sort }}</td>
            <td>{{ $b->is_active ? '✓' : '—' }}</td>
            <td class="actions">
                <a href="{{ route('admin.banners.edit', $b) }}">Изменить</a>
                <form action="{{ route('admin.banners.destroy', $b) }}" method="POST" onsubmit="return confirm('Удалить баннер?')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="muted">Баннеров пока нет — на главной показывается стандартная шапка</td></tr>
    @endforelse
    </tbody>
</table>
@endsection
