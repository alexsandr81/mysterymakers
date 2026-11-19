<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use Localized;

    protected $fillable = [
        'category_id', 'sku', 'slug', 'name_uk', 'name_ru',
        'description_uk', 'description_ru', 'price', 'old_price',
        'stock', 'is_active', 'images',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
        ];
    }

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
    }

    public function getDescriptionAttribute(): ?string
    {
        return $this->localized('description');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Выбранные значения характеристик (Материал: Фанера, Цвет: Тёмный...) */
    public function characteristicValues(): BelongsToMany
    {
        return $this->belongsToMany(CharacteristicValue::class, 'characteristic_value_product');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function approvedReviews(): HasMany
    {
        return $this->reviews()->where('status', 'approved');
    }

    /** Добавляет reviews_count и reviews_avg (только опубликованные отзывы) — для звёзд на карточках */
    public function scopeWithRating(Builder $query): Builder
    {
        return $query
            ->withCount(['reviews as reviews_count' => fn ($r) => $r->where('status', 'approved')])
            ->withAvg(['reviews as reviews_avg' => fn ($r) => $r->where('status', 'approved')], 'rating');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Список URL картинок; если нет ни одной — заглушка */
    public function getImageUrlsAttribute(): array
    {
        $urls = collect($this->images ?? [])
            ->map(fn ($path) => Storage::disk('public')->url($path))
            ->all();

        return $urls ?: [asset('img/placeholder.svg')];
    }

    public function getMainImageAttribute(): string
    {
        return $this->image_urls[0];
    }

    public function getHasDiscountAttribute(): bool
    {
        return $this->old_price !== null && (float) $this->old_price > (float) $this->price;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->has_discount) {
            return 0;
        }

        return (int) round((1 - $this->price / $this->old_price) * 100);
    }

    public function getInStockAttribute(): bool
    {
        return $this->stock > 0;
    }
}
