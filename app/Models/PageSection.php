<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    use Localized;

    protected $fillable = ['page_id', 'title_uk', 'title_ru', 'body_uk', 'body_ru', 'sort'];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
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
