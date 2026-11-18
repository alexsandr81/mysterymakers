<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Characteristic extends Model
{
    use Localized;

    protected $fillable = ['name_uk', 'name_ru', 'is_filterable', 'sort'];

    protected function casts(): array
    {
        return ['is_filterable' => 'boolean'];
    }

    public function getNameAttribute(): ?string
    {
        return $this->localized('name');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CharacteristicValue::class)->orderBy('sort')->orderBy('id');
    }
}
