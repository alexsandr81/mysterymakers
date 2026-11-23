<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class OrderItem extends Model
{
    protected $fillable = ['order_id', 'product_id', 'name', 'image', 'price', 'quantity'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Фото позиции: снимок на момент заказа → фото товара → заглушка */
    public function getImageUrlAttribute(): string
    {
        if ($this->image) {
            return \App\Support\ImageStore::url($this->image);
        }

        return $this->product?->main_image ?? asset('img/placeholder.svg');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
