<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;

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

    public function getTitleAttribute(): ?string
    {
        return $this->localized('title');
    }

    public function getBodyAttribute(): ?string
    {
        return $this->localized('body');
    }
}
