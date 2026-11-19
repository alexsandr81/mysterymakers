<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/** Настройки сайта (название, контакты и т. п.), редактируются в админке. */
class Setting extends Model
{
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /** Значения по умолчанию, пока в админке ничего не сохранено */
    public const DEFAULTS = [
        'site_name' => 'Mystery Makers',
        'tagline_uk' => 'майстерня виробів з фанери',
        'tagline_ru' => 'мастерская изделий из фанеры',
        'description_uk' => 'Майстерня виробів з фанери та МДФ: коробки, брелоки, чайні будиночки, дитячий посуд та багато іншого. А ще — моделі та вироби з 3D-друку. Створюємо з любов’ю до деталей і доставляємо по всій Україні.',
        'description_ru' => 'Мастерская изделий из фанеры и МДФ: коробки, брелки, чайные домики, детская посуда и многое другое. А ещё — модели и изделия с 3D-печати. Создаём с любовью к деталям и доставляем по всей Украине.',
        'phone' => '+380 00 000 00 00', // можно несколько, каждый с новой строки
        'email' => '',
        'notify_email' => '', // куда присылать уведомления о новых заказах (если пусто — на email магазина)
        'address' => '',
        // Новая Почта: данные отправителя для накладных
        'np_sender_ref' => '', 'np_contact_ref' => '', 'np_sender_phone' => '',
        'np_city_ref' => '', 'np_city_title' => '', 'np_wh_ref' => '', 'np_wh_title' => '',
        'np_weight' => '1', 'np_length' => '20', 'np_width' => '20', 'np_height' => '10', 'np_description' => 'Дерев’яні вироби',
    ];

    private static ?array $cache = null;

    public static function get(string $key): string
    {
        if (self::$cache === null) {
            self::$cache = Schema::hasTable('settings') ? self::pluck('value', 'key')->all() : [];
        }

        $value = self::$cache[$key] ?? null;

        // Email и адрес показываются ровно такими, как введены в админке (пусто — не показываются)
        if ($value === null || $value === '') {
            return self::DEFAULTS[$key] ?? '';
        }

        return (string) $value;
    }

    public static function put(string $key, ?string $value): void
    {
        self::updateOrCreate(['key' => $key], ['value' => $value]);
        self::$cache = null;
    }

    /** Название магазина: «Mystery Makers» */
    public static function siteName(): string
    {
        return self::get('site_name');
    }

    /** Подпись под названием на языке сайта: «мастерская деревянных подарков» */
    public static function tagline(): string
    {
        $primary = app()->getLocale() === 'ru' ? 'tagline_ru' : 'tagline_uk';
        $other = $primary === 'tagline_ru' ? 'tagline_uk' : 'tagline_ru';

        return self::get($primary) ?: self::get($other);
    }

    /** Описание магазина на языке сайта (для главной и поисковиков) */
    public static function description(): string
    {
        $primary = app()->getLocale() === 'ru' ? 'description_ru' : 'description_uk';
        $other = $primary === 'description_ru' ? 'description_uk' : 'description_ru';

        return self::get($primary) ?: self::get($other);
    }

    /** Список телефонов (до 5): каждый телефон — отдельная строка в настройках */
    public static function phones(): array
    {
        $lines = preg_split('/\R+/u', self::get('phone')) ?: [];

        return array_slice(array_values(array_filter(array_map('trim', $lines))), 0, 5);
    }

    /**
     * Телефон в международном виде только из цифр: «066 421 94 97» -> «380664219497».
     * null, если из строки нельзя получить номер (тогда ссылки не показываются).
     */
    public static function digits(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);

        if (strlen($digits) === 10 && $digits[0] === '0') {
            $digits = '38' . $digits;
        } elseif (strlen($digits) === 11 && str_starts_with($digits, '80')) {
            $digits = '3' . $digits;
        }

        return strlen($digits) >= 11 && strlen($digits) <= 13 ? $digits : null;
    }
}
