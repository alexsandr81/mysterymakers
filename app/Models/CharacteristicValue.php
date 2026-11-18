<?php

namespace App\Models;

use App\Models\Concerns\Localized;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CharacteristicValue extends Model
{
    use Localized;

    protected $fillable = ['characteristic_id', 'value_uk', 'value_ru', 'sort'];

    public function getTitleAttribute(): ?string
    {
        return $this->localized('value');
    }

    public function characteristic(): BelongsTo
    {
        return $this->belongsTo(Characteristic::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'characteristic_value_product');
    }
}
