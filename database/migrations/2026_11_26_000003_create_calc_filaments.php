<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Филамент для 3D-печати в калькуляторе: цена катушки и её вес → цена за грамм считается сама
        Schema::create('calc_filaments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('spool_price', 10, 2);        // цена катушки, грн
            $table->unsignedInteger('spool_g')->default(1000); // вес катушки, г
            $table->string('link')->nullable();
            $table->date('price_date')->nullable();       // когда цену проверяли в последний раз
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Стартовые данные: PLA — по ценам Prom.ua на 25.11.2026 (обычный PLA 1 кг около 600–700 грн), PETG — ориентир, цену нужно проверить
        foreach ([['PLA', 650, 1000], ['PETG', 750, 1000]] as $i => [$name, $price, $g]) {
            DB::table('calc_filaments')->insert([
                'name' => $name, 'spool_price' => $price, 'spool_g' => $g, 'link' => null, 'price_date' => null,
                'sort' => $i, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('calc_filaments');
    }
};
