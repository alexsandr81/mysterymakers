<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUSES = ['new', 'processing', 'shipped', 'done', 'canceled'];
    public const DELIVERY = ['pickup', 'courier', 'post'];
    public const PAYMENT = ['cash', 'card'];

    protected $fillable = [
        'number', 'user_id', 'name', 'phone', 'email', 'delivery',
        'address', 'payment', 'comment', 'total', 'status',
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
