<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Шрифты сайта. Только такие, где есть кириллица (украинский и русский).
 * Шрифты подгружаются с Google Fonts; пока выбран «Системный», ничего не подгружается.
 * Чтобы добавить шрифт — добавьте строку в LIST: ключ => [название, группа, «семейство» для Google Fonts, запасной вариант CSS].
 */
class Fonts
{
    public const SYSTEM_STACK = 'system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif';

    public const GROUPS = [
        'sans' => 'Без засечек',
        'serif' => 'С засечками',
        'fancy' => 'Декоративные и рукописные',
    ];

    /** ключ => [подпись, группа, семейство в Google Fonts (имя и начертания), запасной CSS] */
    public const LIST = [
        'montserrat' => ['Montserrat', 'sans', 'Montserrat:wght@400;500;700', 'sans-serif'],
        'roboto' => ['Roboto', 'sans', 'Roboto:wght@400;500;700', 'sans-serif'],
        'open-sans' => ['Open Sans', 'sans', 'Open+Sans:wght@400;500;700', 'sans-serif'],
        'nunito' => ['Nunito', 'sans', 'Nunito:wght@400;500;700', 'sans-serif'],
        'rubik' => ['Rubik', 'sans', 'Rubik:wght@400;500;700', 'sans-serif'],
        'manrope' => ['Manrope', 'sans', 'Manrope:wght@400;500;700', 'sans-serif'],
        'fira-sans' => ['Fira Sans', 'sans', 'Fira+Sans:wght@400;500;700', 'sans-serif'],
        'raleway' => ['Raleway', 'sans', 'Raleway:wght@400;500;700', 'sans-serif'],
        'jost' => ['Jost', 'sans', 'Jost:wght@400;500;700', 'sans-serif'],
        'pt-sans' => ['PT Sans', 'sans', 'PT+Sans:wght@400;700', 'sans-serif'],
        'comfortaa' => ['Comfortaa', 'sans', 'Comfortaa:wght@400;500;700', 'sans-serif'],
        'lora' => ['Lora', 'serif', 'Lora:wght@400;500;700', 'serif'],
        'playfair' => ['Playfair Display', 'serif', 'Playfair+Display:wght@400;500;700', 'serif'],
        'merriweather' => ['Merriweather', 'serif', 'Merriweather:wght@400;700', 'serif'],
        'pt-serif' => ['PT Serif', 'serif', 'PT+Serif:wght@400;700', 'serif'],
        'cormorant' => ['Cormorant Garamond', 'serif', 'Cormorant+Garamond:wght@400;500;700', 'serif'],
        'source-serif' => ['Source Serif 4', 'serif', 'Source+Serif+4:wght@400;500;700', 'serif'],
        'literata' => ['Literata', 'serif', 'Literata:wght@400;500;700', 'serif'],
        'noto-serif' => ['Noto Serif', 'serif', 'Noto+Serif:wght@400;500;700', 'serif'],
        'bitter' => ['Bitter', 'serif', 'Bitter:wght@400;500;700', 'serif'],
        'philosopher' => ['Philosopher', 'serif', 'Philosopher:wght@400;700', 'serif'],
        'caveat' => ['Caveat', 'fancy', 'Caveat:wght@400;500;700', 'cursive'],
        'marck-script' => ['Marck Script', 'fancy', 'Marck+Script', 'cursive'],
        'pacifico' => ['Pacifico', 'fancy', 'Pacifico', 'cursive'],
        'lobster' => ['Lobster', 'fancy', 'Lobster', 'cursive'],
        'amatic' => ['Amatic SC', 'fancy', 'Amatic+SC:wght@400;700', 'cursive'],
        'neucha' => ['Neucha', 'fancy', 'Neucha', 'cursive'],
        'oswald' => ['Oswald', 'fancy', 'Oswald:wght@400;500;700', 'sans-serif'],
        'unbounded' => ['Unbounded', 'fancy', 'Unbounded:wght@400;500;700', 'sans-serif'],
    ];

    /** Допустимые значения для поля «основной шрифт» */
    public static function keys(): array
    {
        return array_merge(['system'], array_keys(self::LIST));
    }

    /** Допустимые значения для «заголовков» и «названия»: ещё «как основной / как заголовки» */
    public static function keysWithSame(): array
    {
        return array_merge(['same'], self::keys());
    }

    private static function family(string $key): string
    {
        return isset(self::LIST[$key]) ? '"' . self::LIST[$key][0] . '", ' . self::LIST[$key][3] : self::SYSTEM_STACK;
    }

    /** Выбранные шрифты с учётом «как основной» */
    public static function selected(): array
    {
        $body = Setting::get('font_body');
        $body = in_array($body, self::keys(), true) ? $body : 'system';
        $heading = Setting::get('font_heading');
        $heading = ($heading === 'same' || ! in_array($heading, self::keys(), true)) ? $body : $heading;
        $logo = Setting::get('font_logo');
        $logo = ($logo === 'same' || ! in_array($logo, self::keys(), true)) ? $heading : $logo;

        return ['body' => $body, 'heading' => $heading, 'logo' => $logo];
    }

    /** Ссылка на Google Fonts для нужных шрифтов (null — подгружать нечего) */
    public static function url(array $keys): ?string
    {
        $parts = [];
        foreach (array_unique($keys) as $k) {
            if (isset(self::LIST[$k])) {
                $parts[] = 'family=' . self::LIST[$k][2];
            }
        }

        return $parts ? 'https://fonts.googleapis.com/css2?' . implode('&', $parts) . '&display=swap' : null;
    }

    public static function urlAll(): string
    {
        return self::url(array_keys(self::LIST));
    }

    /** Содержимое <style> с переменными шрифтов */
    public static function css(): string
    {
        $s = self::selected();

        return ':root{--font-body:' . self::family($s['body']) . ';--font-heading:' . self::family($s['heading']) . ';--font-logo:' . self::family($s['logo']) . ';}';
    }

    /** Данные для живого предпросмотра в настройках: ключ => CSS font-family */
    public static function previewMap(): array
    {
        $map = ['system' => self::SYSTEM_STACK];
        foreach (self::LIST as $k => $_) {
            $map[$k] = self::family($k);
        }

        return $map;
    }
}
