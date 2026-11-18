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

        return ($value === null || $value === '') && $key !== 'address'
            ? (self::DEFAULTS[$key] ?? '')
            : (string) $value;
    }

    public static function put(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }
}
