<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calc_materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('thickness', 5, 1);          // мм
            $table->unsignedInteger('sheet_w');          // размер листа, мм
            $table->unsignedInteger('sheet_h');
            $table->decimal('sheet_price', 10, 2);       // цена листа, грн
            $table->decimal('cut_rate', 8, 2);           // резка, грн за погонный метр
            $table->string('link')->nullable();          // что за материал / где купить
            $table->date('price_date')->nullable();      // когда цену проверяли в последний раз
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Стартовые данные — из старой таблицы Excel (цены листов нужно проверить и обновить)
        $rows = [
            ['Фанера', 3, 1525, 1525, 850, 5, 'Фанера ФК 3мм 1525×1525мм'],
            ['Фанера', 4, 1525, 1525, 978, 6, 'Фанера ФК 4мм 1525×1525мм'],
            ['Фанера', 5, 1525, 1525, 1119, 8, 'Фанера ФК 5мм 1525×1525мм'],
            ['Фанера', 6, 1525, 1525, 1260, 8, 'Фанера ФК 6мм 1525×1525мм'],
            ['МДФ/ХДФ', 2.5, 2070, 1422, 439, 5, 'ХДФ Pfleiderer 2070×1422 мм (ламинированная)'],
            ['МДФ/ХДФ', 3, 2800, 2070, 773, 6, 'ХДФ плита 3мм белая (ламинированная)'],
            ['МДФ/ХДФ', 6, 2800, 2070, 1030, 8, 'МДФ плита 6мм 2800×2070'],
        ];
        foreach ($rows as $i => [$name, $t, $w, $h, $price, $cut, $link]) {
            DB::table('calc_materials')->insert([
                'name' => $name, 'thickness' => $t, 'sheet_w' => $w, 'sheet_h' => $h,
                'sheet_price' => $price, 'cut_rate' => $cut, 'link' => $link, 'price_date' => null,
                'sort' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calc_materials');
    }
};
