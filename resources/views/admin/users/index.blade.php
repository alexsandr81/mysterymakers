@extends('admin.layout')

@section('title', 'Пользователи')

@section('content')
<div class="admin-head">
    <h1>Пользователи</h1>
</div>

<form method="GET" class="search search--admin">
    <input type="search" name="q" value="{{ $q }}" placeholder="Имя, email или телефон">
    <button class="btn btn--brand">Найти</button>
</form>

<table class="table">
    <thead><tr><th>Имя</th><th>Email</th><th>Телефон</th><th>Заказов</th><th>Роль</th><th>Регистрация</th><th></th></tr></thead>
    <tbody>
    @forelse($users as $u)
        <tr>
            <td>{{ $u->name }}</td>
            <td>{{ $u->email }}</td>
            <td>{{ $u->phone ?: '—' }}</td>
            <td>{{ $u->orders_count }}</td>
            <td>{{ ['admin' => 'Администратор', 'moderator' => 'Модератор', 'customer' => 'Покупатель'][$u->role()] }}</td>
            <td>{{ $u->created_at?->format('d.m.Y') }}</td>
            <td class="actions">
                <a href="{{ route('admin.users.edit', $u) }}">Изменить</a>
                @if($u->id !== auth()->id())
                <form action="{{ route('admin.users.destroy', $u) }}" method="POST" onsubmit="return confirm('Удалить пользователя? Его заказы сохранятся.')">
                    @csrf @method('DELETE')
                    <button class="link-btn link-btn--danger">Удалить</button>
                </form>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="7" class="muted">Никого не найдено</td></tr>
    @endforelse
    </tbody>
</table>
<div class="pager">{{ $users->links('partials.pagination') }}</div>
@endsection
