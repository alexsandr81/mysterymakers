<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Настройки сайта (контакты в подвале и т. п.), редактируются в админке. */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /** Значения по умолчанию, пока в админке ничего не сохранено */
    public const DEFAULTS = [
        'phone' => '+380 00 000 00 00',
        'email' => 'info@mysterymakers.local',
        'address' => '',
    ];

    private static ?array $cache = null;

    public static function get(string $key): string
    {
        if (self::$cache === null) {
            self::$cache = Schema::hasTable('settings') ? self::pluck('value', 'key')->all() : [];
        }

        $value = self::$cache[$key] ?? null;

        if (($value === null || $value === '') && $key !== 'address') {
            // Пока email не задан в админке, берём адрес отправителя почты из .env (реальный), а не заглушку
            if ($key === 'email' && config('mail.from.address') && config('mail.from.address') !== 'hello@example.com') {
                return (string) config('mail.from.address');
            }

            return self::DEFAULTS[$key] ?? '';
        }

        return (string) $value;
    }

    public static function put(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }
}
