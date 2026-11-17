<?php

namespace App\Models\Concerns;

/**
 * Достаёт значение поля на текущем языке: name_uk / name_ru.
 * Если на текущем языке пусто — берёт другой язык.
 */
trait Localized
{
    protected function localized(string $field): ?string
    {
        $locale = app()->getLocale();
        $fallback = $locale === 'uk' ? 'ru' : 'uk';

        return $this->{$field . '_' . $locale} ?: $this->{$field . '_' . $fallback};
    }
}
