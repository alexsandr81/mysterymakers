<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use Localized;

    protected $fillable = ['parent_id', 'slug', 'name_uk', 'name_ru', 'sort'];

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
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
