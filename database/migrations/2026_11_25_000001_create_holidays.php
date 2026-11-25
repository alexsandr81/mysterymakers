<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('name_uk');
            $table->string('name_ru');
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true); // скрытый праздник не показывается в фильтре каталога
            $table->timestamps();
        });

        Schema::create('holiday_product', function (Blueprint $table) {
            $table->foreignId('holiday_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->primary(['holiday_id', 'product_id']);
        });

        // Стартовый набор — лишние можно удалить или скрыть в админке (раздел «Праздники»)
        $starter = [
            ['Новий рік', 'Новый год'], ['Різдво', 'Рождество'], ['День святого Валентина', 'День святого Валентина'],
            ['8 Березня', '8 Марта'], ['Великдень', 'Пасха'], ['День народження', 'День рождения'],
            ['Випускний', 'Выпускной'], ['Весілля', 'Свадьба'],
        ];
        foreach ($starter as $i => [$uk, $ru]) {
            DB::table('holidays')->insert(['name_uk' => $uk, 'name_ru' => $ru, 'sort' => $i, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('holiday_product');
        Schema::dropIfExists('holidays');
    }
};
