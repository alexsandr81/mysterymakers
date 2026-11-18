<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Администратор для первого входа. СМЕНИТЕ ПАРОЛЬ после установки!
        // firstOrCreate: при повторном запуске сида уже изменённый пароль не сбрасывается.
        User::firstOrCreate(
            ['email' => 'admin@mysterymakers.local'],
            ['name' => 'Admin', 'password' => Hash::make('admin12345'), 'is_admin' => true]
        );

        $categories = [
            ['minibary', 'Мінібари', 'Мини-бары'],
            ['korobky', 'Коробки', 'Коробки'],
            ['podarunky', 'Подарункові набори', 'Подарочные наборы'],
            ['dekor', 'Декор', 'Декор'],
        ];

        $ids = [];
        foreach ($categories as $i => [$slug, $uk, $ru]) {
            $ids[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name_uk' => $uk, 'name_ru' => $ru, 'sort' => $i]
            )->id;
        }

        $products = [
            ['minibary', 'MM-001', 'mini-bar-lokomotyv', 'Мінібар «Локомотив»', 'Мини-бар «Локомотив»', 3200, 3900, 5],
            ['minibary', 'MM-002', 'mini-bar-cysterna', 'Мінібар «Цистерна»', 'Мини-бар «Цистерна»', 2800, null, 8],
            ['korobky', 'MM-010', 'korobka-sertse', 'Коробка «Серце»', 'Коробка «Сердце»', 450, 600, 25],
            ['korobky', 'MM-011', 'korobka-kvadrat', 'Коробка квадратна', 'Коробка квадратная', 320, null, 40],
            ['podarunky', 'MM-020', 'nabir-valentyn', 'Набір до Дня закоханих', 'Набор ко Дню влюблённых', 990, null, 0],
            ['dekor', 'MM-030', 'dekor-pidstavka', 'Підставка з фанери', 'Подставка из фанеры', 180, null, 60],
        ];

        foreach ($products as [$cat, $sku, $slug, $nameUk, $nameRu, $price, $old, $stock]) {
            // firstOrCreate: повторный запуск не затирает цены и описания, изменённые в админке
            Product::firstOrCreate(['sku' => $sku], [
                'category_id' => $ids[$cat],
                'slug' => $slug,
                'name_uk' => $nameUk,
                'name_ru' => $nameRu,
                'description_uk' => 'Демо-товар. Замініть опис у адмінці.',
                'description_ru' => 'Демо-товар. Замените описание в админке.',
                'price' => $price,
                'old_price' => $old,
                'stock' => $stock,
                'is_active' => true,
            ]);
        }

        $this->call(ContentSeeder::class);
    }
}
