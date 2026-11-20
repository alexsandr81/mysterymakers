<div class="section-edit">
    <div class="section-edit__head">
        <strong>Раздел <span class="section-num"></span></strong>
        <span class="section-edit__tools">
            <button type="button" class="btn btn--sm" data-move-up title="Поднять выше">▲</button>
            <button type="button" class="btn btn--sm" data-move-down title="Опустить ниже">▼</button>
            <button type="button" class="link-btn link-btn--danger" data-remove-section>Удалить раздел</button>
        </span>
    </div>
    <div class="form__cols">
        <label>Заголовок (укр.)
            <input type="text" name="sections[{{ $i }}][title_uk]" value="{{ $row['title_uk'] ?? '' }}" placeholder="Графік роботи">
        </label>
        <label>Заголовок (рус.)
            <input type="text" name="sections[{{ $i }}][title_ru]" value="{{ $row['title_ru'] ?? '' }}" placeholder="График работы">
        </label>
    </div>
    <div class="form__cols">
        <label>Текст (укр.)
            <textarea name="sections[{{ $i }}][body_uk]" rows="4">{{ $row['body_uk'] ?? '' }}</textarea>
        </label>
        <label>Текст (рус.)
            <textarea name="sections[{{ $i }}][body_ru]" rows="4">{{ $row['body_ru'] ?? '' }}</textarea>
        </label>
    </div>
</div>
