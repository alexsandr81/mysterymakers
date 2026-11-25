<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Localized;

    protected $fillable = ['parent_id', 'slug', 'name_uk', 'name_ru', 'sort', 'sku_start'];

    /** Размер блока артикулов: категория верхнего уровня — 10 000, подкатегория — 1000 внутри блока родителя */
    public const TOP_BLOCK = 10000;
    public const SUB_BLOCK = 1000;

    public function skuBlockSize(): int
    {
        return $this->parent_id ? self::SUB_BLOCK : self::TOP_BLOCK;
    }

    /**
     * С какого числа начинать блок артикулов новой категории (null — в блоке родителя нет места).
     * Верхний уровень: 10000, 20000, 30000…
     * Подкатегория: внутри блока родителя — 11000, 12000… (товары самого родителя — 10000–10999).
     */
    public static function nextSkuStart(?int $parentId): ?int
    {
        if (! $parentId) {
            $max = (int) static::whereNull('parent_id')->where('sku_start', '>=', self::TOP_BLOCK)->max('sku_start');

            return $max ? $max + self::TOP_BLOCK : self::TOP_BLOCK;
        }

        $parent = static::find($parentId);
        $base = (int) ($parent?->sku_start ?: 0);
        if ($base < self::TOP_BLOCK) {
            return null;
        }
        $max = (int) static::where('parent_id', $parentId)
            ->where('sku_start', '>', $base)->where('sku_start', '<', $base + self::TOP_BLOCK)->max('sku_start');
        $next = $max ? $max + self::SUB_BLOCK : $base + self::SUB_BLOCK;

        return $next < $base + self::TOP_BLOCK ? $next : null;
    }

    /** Следующий свободный артикул категории: от начала её блока и дальше +1 */
    public function nextSku(): string
    {
        $start = (int) ($this->sku_start ?: self::nextSkuStart($this->parent_id) ?: self::TOP_BLOCK);
        $end = $start + $this->skuBlockSize() - 1;
        $used = Product::pluck('sku')->filter(fn ($s) => ctype_digit((string) $s))->map(fn ($s) => (int) $s)->flip();

        $max = Product::where('category_id', $this->id)->pluck('sku')
            ->filter(fn ($s) => ctype_digit((string) $s) && (int) $s >= $start && (int) $s <= $end)->map(fn ($s) => (int) $s)->max();

        $n = $max ? $max + 1 : $start;
        while (isset($used[$n])) {
            $n++;
        }

        return (string) $n;
    }

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
    }

    /** Уникальный slug из названия (для категорий, созданных «на лету» из формы товара) */
    public static function makeSlug(string $name): string
    {
        $base = \Illuminate\Support\Str::slug($name, '-', 'uk') ?: 'category';
        $slug = $base;
        $i = 2;
        while (static::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** id этой категории и всех вложенных — чтобы каталог родителя показывал товары детей */
    public function selfAndChildrenIds(): array
    {
        return array_merge([$this->id], $this->children()->pluck('id')->all());
    }
}
