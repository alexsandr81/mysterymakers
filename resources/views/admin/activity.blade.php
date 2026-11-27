@extends('admin.layout')

@section('title', 'Действия модератора')

@section('content')
<div class="admin-head">
    <h1>Действия модератора</h1>
    @if($items->total() > 0)
        @php $who = $userId ? ($moderators->firstWhere('id', $userId)?->name ?? 'выбранного модератора') : null; @endphp
        <form action="{{ route('admin.activity.clear') }}" method="POST" onsubmit="return confirm('{{ $who ? 'Удалить из журнала все записи: ' . e($who) . '?' : 'Очистить журнал целиком? Все записи о действиях модераторов будут удалены без возможности восстановления.' }}')">
            @csrf @method('DELETE')
            <input type="hidden" name="user" value="{{ $userId ?: '' }}">
            <button class="btn btn--danger">🗑 {{ $who ? 'Очистить записи: ' . $who : 'Очистить журнал' }}</button>
        </form>
    @endif
</div>
<p class="muted">Здесь записано всё, что делают пользователи с ролью «Модератор»: добавление, изменение и удаление. Новые записи подсвечены. Когда приходит новое действие, наверху появляется уведомление, а в меню счётчик.</p>

@if($moderators->count() > 1)
    <form method="GET" class="search search--admin">
        <select name="user" onchange="this.form.submit()">
            <option value="">Все модераторы</option>
            @foreach($moderators as $m)
                <option value="{{ $m->id }}" @selected($userId === $m->id)>{{ $m->name }}</option>
            @endforeach
        </select>
    </form>
@endif

<table class="table table--compact">
    <thead><tr><th>Когда</th><th>Кто</th><th>Что сделано</th><th>Над чем</th></tr></thead>
    <tbody>
    @forelse($items as $a)
        <tr class="{{ (! $seenAt || $a->created_at > $seenAt) ? 'act-new' : '' }}">
            <td class="nowrap muted">{{ $a->created_at->format('d.m.Y H:i') }}</td>
            <td class="nowrap">{{ $a->user_name }}</td>
            <td>{{ $a->label }}</td>
            <td>@if($a->link)<a href="{{ $a->link }}" target="_blank">{{ $a->subject ?: 'открыть' }}</a>@else{{ $a->subject ?: '—' }}@endif</td>
        </tr>
    @empty
        <tr><td colspan="4" class="muted">Пока ничего нет. Записи появятся, когда модератор начнёт работать.</td></tr>
    @endforelse
    </tbody>
</table>
<div class="pager">{{ $items->links('partials.pagination') }}</div>
@endsection
