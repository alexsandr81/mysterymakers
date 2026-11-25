<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** Праздник / повод (Новый год, 8 Марта…) — тематический фильтр товаров, независимый от категорий */
class Holiday extends Model
{
    use Localized;

    protected $fillable = ['name_uk', 'name_ru', 'sort', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }
}
