<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Филамент для калькулятора цены: цена катушки и вес катушки */
class CalcFilament extends Model
{
    protected $fillable = ['name', 'spool_price', 'spool_g', 'link', 'price_date', 'sort'];

    protected $casts = ['price_date' => 'date'];

    /** Цена за грамм, грн */
    public function pricePerGram(): float
    {
        return $this->spool_g > 0 ? (float) $this->spool_price / $this->spool_g : 0.0;
    }
}
