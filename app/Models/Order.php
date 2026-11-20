<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['new', 'processing', 'shipped', 'done', 'canceled'];
    public const DELIVERY = ['nova_poshta', 'np_address', 'ukrposhta'];
    // Оплата картой будет добавлена позже (онлайн-эквайринг); 'card' оставлен в названиях для совместимости
    public const PAYMENT = ['cod'];

    protected $fillable = [
        'number', 'user_id', 'name', 'phone', 'email', 'delivery',
        'address', 'city', 'branch', 'np_city_ref', 'np_warehouse_ref', 'payment', 'comment', 'total', 'status', 'locale', 'np_ttn', 'np_ttn_ref', 'np_status', 'np_is_postomat', 'np_street_ref', 'np_street', 'np_building', 'np_flat',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
