<?php

namespace Database\Seeders;

use App\Models\Characteristic;
use App\Models\Page;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Демо-страницы и характеристики. Можно запускать повторно и на рабочей базе:
 * уже существующие страницы и характеристики НЕ перезаписываются.
 *
 *   php artisan db:seed --class=ContentSeeder
 */
class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->pages();
        $this->characteristics();
    }

    private function pages(): void
    {
        $pages = [
            [
                'slug' => 'delivery',
                'sort' => 1,
                'title_uk' => 'Доставка і оплата',
                'title_ru' => 'Доставка и оплата',
                'body_uk' => "Самовивіз — безкоштовно.\nКур'єр по місту — за домовленістю.\nНова пошта — по тарифах перевізника.\n\nОплата: готівкою або карткою при отриманні.",
                'body_ru' => "Самовывоз — бесплатно.\nКурьер по городу — по договорённости.\nНовая почта — по тарифам перевозчика.\n\nОплата: наличными или картой при получении.",
            ],
            [
                'slug' => 'about',
                'sort' => 2,
                'title_uk' => 'Про нас',
                'title_ru' => 'О нас',
                'body_uk' => "MysteryMakers — майстерня виробів з фанери та МДФ.\nЛазерне різання, ручне фарбування та складання.\n\nЗамініть цей текст у адмінці: Сторінки → Про нас.",
                'body_ru' => "MysteryMakers — мастерская изделий из фанеры и МДФ.\nЛазерная резка, ручная покраска и сборка.\n\nЗамените этот текст в админке: Страницы → О нас.",
            ],
            [
                'slug' => 'contacts',
                'sort' => 3,
                'title_uk' => 'Контакти',
                'title_ru' => 'Контакты',
                'body_uk' => "Телефон: +380 00 000 00 00\nEmail: info@mysterymakers.local\n\nЗамініть контакти у адмінці: Сторінки → Контакти.",
                'body_ru' => "Телефон: +380 00 000 00 00\nEmail: info@mysterymakers.local\n\nЗамените контакты в админке: Страницы → Контакты.",
            ],
        ];

        foreach ($pages as $page) {
            Page::firstOrCreate(['slug' => $page['slug']], $page + ['is_published' => true, 'show_in_footer' => true]);
        }
    }

    private function characteristics(): void
    {
        $definitions = [
            ['Матеріал', 'Материал', 1, [['Фанера', 'Фанера'], ['МДФ', 'МДФ']]],
            ['Колір', 'Цвет', 2, [['Натуральний', 'Натуральный'], ['Темний (морилка)', 'Тёмный (морилка)'], ['Білий', 'Белый']]],
        ];

        foreach ($definitions as [$nameUk, $nameRu, $sort, $values]) {
            $characteristic = Characteristic::firstOrCreate(
                ['name_uk' => $nameUk],
                ['name_ru' => $nameRu, 'sort' => $sort, 'is_filterable' => true]
            );

            foreach ($values as $i => [$uk, $ru]) {
                $characteristic->values()->firstOrCreate(['value_uk' => $uk], ['value_ru' => $ru, 'sort' => $i]);
            }
        }

        // Демо-привязка: только товарам, у которых характеристики ещё не заданы
        $material = Characteristic::where('name_uk', 'Матеріал')->first();
        $color = Characteristic::where('name_uk', 'Колір')->first();
        $plywood = $material?->values()->where('value_uk', 'Фанера')->first();
        $natural = $color?->values()->where('value_uk', 'Натуральний')->first();

        if ($plywood && $natural) {
            Product::doesntHave('characteristicValues')->each(
                fn (Product $p) => $p->characteristicValues()->sync([$plywood->id, $natural->id])
            );
        }
    }
}
