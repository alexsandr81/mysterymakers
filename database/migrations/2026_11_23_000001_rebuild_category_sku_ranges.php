<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Новая схема артикулов: категория верхнего уровня — блок 10000, подкатегории — блоки по 1000 внутри него.
     * Артикулы уже созданных товаров НЕ меняются, меняются только стартовые числа категорий для новых товаров.
     */
    public function up(): void
    {
        $all = DB::table('categories')->orderBy('sort')->orderBy('id')->get();
        $top = $all->filter(fn ($c) => ! $c->parent_id || ! $all->contains('id', $c->parent_id))->values();

        foreach ($top as $i => $parent) {
            $base = ($i + 1) * 10000;
            DB::table('categories')->where('id', $parent->id)->update(['sku_start' => $base]);

            foreach ($all->where('parent_id', $parent->id)->values() as $k => $child) {
                DB::table('categories')->where('id', $child->id)->update(['sku_start' => $k < 9 ? $base + ($k + 1) * 1000 : null]);
            }
        }
    }

    public function down(): void
    {
        // Возврат не нужен: стартовые числа влияют только на новые артикулы
    }
};
