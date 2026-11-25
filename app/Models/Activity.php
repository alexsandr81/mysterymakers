<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Запись журнала действий модератора (см. App\Http\Middleware\LogStaffActions) */
class Activity extends Model
{
    protected $table = 'activity_log';
    public const UPDATED_AT = null;
    protected $fillable = ['user_id', 'user_name', 'label', 'subject', 'url'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Новые для администратора записи: появились после его последнего просмотра журнала */
    public static function unseenCount(User $admin): int
    {
        return static::when($admin->activity_seen_at, fn ($q) => $q->where('created_at', '>', $admin->activity_seen_at))->count();
    }

    /** Одной строкой: «Жена: Изменён товар «Шкатулка»» */
    public function getTextAttribute(): string
    {
        return $this->user_name . ': ' . $this->label . ($this->subject ? ' «' . $this->subject . '»' : '');
    }
}
