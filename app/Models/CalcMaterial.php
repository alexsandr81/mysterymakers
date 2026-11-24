<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Материал для калькулятора цены: лист, его цена и тариф резки */
class CalcMaterial extends Model
{
    protected $fillable = ['name', 'thickness', 'sheet_w', 'sheet_h', 'sheet_price', 'cut_rate', 'link', 'price_date', 'sort'];

    protected $casts = ['price_date' => 'date'];

    public function label(): string
    {
        return $this->name . ' ' . rtrim(rtrim(number_format((float) $this->thickness, 1, '.', ''), '0'), '.') . ' мм';
    }
}
