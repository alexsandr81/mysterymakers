<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use Localized;

    protected $fillable = [
        'slug', 'title_uk', 'title_ru', 'body_uk', 'body_ru',
        'is_published', 'show_in_footer', 'sort',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort')->orderBy('id');
    }

    public function getTitleAttribute(): ?string
    {
        return $this->localized('title');
    }

    public function getBodyAttribute(): ?string
    {
        return $this->localized('body');
    }
}
