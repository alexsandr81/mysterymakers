<?php

namespace App\Support;

/**
 * Разделы админки, которые администратор может открыть или закрыть модератору.
 * Ключ совпадает со второй частью имени маршрута: admin.<ключ>.… (например, admin.products.index → products).
 * Дашборд открыт всегда; «Пользователи» и «Действия модератора» — только администратору, их здесь нет.
 */
class AdminSections
{
    /** ключ => [значок, название, имя маршрута страницы-списка] */
    public const LIST = [
        'orders' => ['🛒', 'Заказы', 'admin.orders.index'],
        'products' => ['📦', 'Товары', 'admin.products.index'],
        'reviews' => ['💬', 'Отзывы', 'admin.reviews.index'],
        'categories' => ['📂', 'Категории', 'admin.categories.index'],
        'holidays' => ['🎉', 'Праздники', 'admin.holidays.index'],
        'characteristics' => ['🏷', 'Характеристики', 'admin.characteristics.index'],
        'banners' => ['🖼', 'Баннеры', 'admin.banners.index'],
        'pages' => ['📄', 'Страницы', 'admin.pages.index'],
        'settings' => ['⚙', 'Настройки', 'admin.settings.edit'],
        'calculator' => ['🧮', 'Калькулятор', 'admin.calculator.index'],
        'novaposhta' => ['📦', 'Новая Почта', 'admin.novaposhta.edit'],
        'backups' => ['💾', 'Копии', 'admin.backups.index'],
    ];

    public static function keys(): array
    {
        return array_keys(self::LIST);
    }

    /** Раздел по имени маршрута (admin.products.edit → products); null — маршрут не относится к разделам */
    public static function fromRoute(?string $routeName): ?string
    {
        $parts = explode('.', (string) $routeName);
        $key = ($parts[0] ?? '') === 'admin' ? ($parts[1] ?? null) : null;

        return $key !== null && isset(self::LIST[$key]) ? $key : null;
    }
}
