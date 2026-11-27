@php use App\Support\Money; @endphp
@php
    $cat = $p->category;
    $top = $cat->parent ?? $cat;
@endphp
<tr data-g="{{ $top->id }}" data-s="{{ $cat->parent_id ? $cat->id : '' }}" {!! $attrs ?? '' !!}>
    <td class="{{ ! empty($indent) ? 'row-edge row-shift row-shift--' . $indent : '' }}"><input type="checkbox" name="ids[]" form="bulk-form" value="{{ $p->id }}" data-price="{{ (float) $p->price }}" data-g="{{ $top->id }}" data-s="{{ $cat->parent_id ? $cat->id : '' }}"></td>
    <td class="thumb {{ ! empty($indent) ? 'row-shift row-shift--' . $indent : '' }}" data-col="photo"><img src="{{ $p->main_image }}" alt=""></td>
    <td class="{{ ! empty($indent) ? 'row-indent row-indent--' . $indent : '' }}"><a href="{{ route('product', $p) }}" target="_blank" title="Открыть страницу товара на сайте">{{ $p->name_ru }}</a></td>
    <td data-col="sku"><a href="{{ route('product', $p) }}" target="_blank" title="Открыть страницу товара на сайте">{{ $p->sku }}</a></td>
        <td data-col="category"><span class="badge badge--sm badge--top">{{ $top->name_ru }}</span></td>
    <td data-col="sub">@if($cat->parent_id)<span class="badge badge--sm badge--sub">{{ $cat->name_ru }}</span>@else<span class="muted" title="Товар лежит прямо в категории, без подкатегории">—</span>@endif</td>
    <td data-col="price" class="nowrap">@if($p->has_price){{ Money::format($p->price) }}@else<span class="text-warn" title="Цена не задана: на сайте «Цена уточняется»">нет цены</span>@endif</td>
    <td data-col="stock" class="{{ $p->stock < 5 ? 'text-warn' : '' }}">{{ $p->stock }}</td>
    <td data-col="active">{{ $p->is_active ? '✓' : '—' }}</td>
    <td data-col="review" class="nowrap">@if($p->needs_review)<span class="badge badge--sm badge--review" title="Менял: {{ $p->modifier?->name ?? '—' }}, {{ $p->updated_at?->format('d.m.Y H:i') }}">⏳ проверить</span>@endif</td>
    <td data-col="created" class="muted nowrap">{{ $p->created_at?->format('d.m.Y') }}</td>
    <td class="actions">
        @if($p->drive_folder_id)
            <a href="{{ \App\Services\GoogleDrive::url($p->drive_folder_id) }}" target="_blank" title="Папка на Google Диске">📁</a>
        @elseif(\App\Services\GoogleDrive::enabled())
            <form action="{{ route('admin.products.drive', $p) }}" method="POST">@csrf<button class="link-btn" title="Создать папку на Google Диске">📁+</button></form>
        @endif
        @if($isAdmin && $p->needs_review)
            <form action="{{ route('admin.products.reviewed', $p) }}" method="POST">@csrf<button class="link-btn review-ok" title="Проверено: снять метку">✔</button></form>
        @elseif($isAdmin)
            <form action="{{ route('admin.products.to-review', $p) }}" method="POST">@csrf<button class="link-btn" title="Отправить на проверку">⏳</button></form>
        @endif
        <a href="{{ route('admin.products.edit', $p) }}" title="Изменить">✎</a>
        <form action="{{ route('admin.products.destroy', $p) }}" method="POST" onsubmit="return confirm('Удалить товар?')">
            @csrf @method('DELETE')
            <button class="link-btn link-btn--danger" title="Удалить">✕</button>
        </form>
    </td>
</tr>
