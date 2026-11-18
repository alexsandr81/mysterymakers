<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    use Localized;

    protected $fillable = [
        'title_uk', 'title_ru', 'subtitle_uk', 'subtitle_ru',
        'image', 'url', 'sort', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function getTitleAttribute(): ?string
    {
        return $this->localized('title');
    }

    public function getSubtitleAttribute(): ?string
    {
        return $this->localized('subtitle');
    }

    public function getImageUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->image);
    }
}
