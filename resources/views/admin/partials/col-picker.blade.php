{{-- Кнопка «Столбцы». Параметры: $table (ключ таблицы), $cols (['ключ' => 'Название']) --}}
<div class="colpick">
    <details>
        <summary>⚙ Столбцы</summary>
        <div class="colpick__list">
            @foreach($cols as $key => $label)
                <label><input type="checkbox" data-colpick="{{ $key }}" data-table="{{ $table }}" checked> {{ $label }}</label>
            @endforeach
            <button type="button" class="link-btn" data-colpick-reset="{{ $table }}" title="Вернуть стандартный набор столбцов и ширину">Сбросить</button>
            <small class="muted">Ширину столбца можно менять, потянув за край заголовка.</small>
        </div>
    </details>
</div>
