@extends('admin.layout')

@section('title', 'Отзывы')

@section('content')
<h1>Отзывы</h1>

<div class="filters-inline">
    @foreach(['pending' => 'На модерации', 'approved' => 'Опубликованные', 'rejected' => 'Скрытые', 'all' => 'Все'] as $key => $label)
        <a href="{{ route('admin.reviews.index', ['status' => $key]) }}" class="{{ $status === $key ? 'active' : '' }}">
            {{ $label }}@if($key !== 'all') ({{ $counts[$key] ?? 0 }})@endif
        </a>
    @endforeach
</div>

@forelse($reviews as $r)
    <div class="panel review-admin">
        <div class="review-admin__head">
            <div>
                @include('partials.stars', ['value' => $r->rating])
                <strong>{{ $r->user?->name }}</strong>
                <a href="{{ route('admin.users.edit', $r->user_id) }}" class="muted">({{ $r->user?->email }})</a>
            </div>
            <span class="status status--{{ $r->status === 'approved' ? 'done' : ($r->status === 'rejected' ? 'canceled' : 'new') }}">
                {{ ['pending' => 'На модерации', 'approved' => 'Опубликован', 'rejected' => 'Скрыт'][$r->status] }}
            </span>
        </div>
        <div class="muted">
            Товар: <a href="{{ route('product', $r->product) }}" target="_blank">{{ $r->product->sku }} · {{ $r->product->name_ru }}</a>
            · {{ $r->created_at->format('d.m.Y H:i') }}
        </div>
        <p>{!! nl2br(e($r->body)) !!}</p>
        <div class="actions">
            @if($r->status !== 'approved')
                <form action="{{ route('admin.reviews.status', $r) }}" method="POST">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="approved">
                    <button class="btn btn--brand">✓ Опубликовать</button>
                </form>
            @endif
            @if($r->status !== 'rejected')
                <form action="{{ route('admin.reviews.status', $r) }}" method="POST">
                    @csrf @method('PATCH')
                    <input type="hidden" name="status" value="rejected">
                    <button class="btn">Скрыть</button>
                </form>
            @endif
            <form action="{{ route('admin.reviews.destroy', $r) }}" method="POST" onsubmit="return confirm('Удалить отзыв навсегда?')">
                @csrf @method('DELETE')
                <button class="btn btn--danger">Удалить</button>
            </form>
        </div>
    </div>
@empty
    <p class="muted">Отзывов в этом разделе нет.</p>
@endforelse

<div class="pager">{{ $reviews->links('partials.pagination') }}</div>
@endsection
