<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->unsignedInteger('sku_start')->nullable(); // с какого числа начинаются артикулы категории
        });

        // Существующим категориям раздаём диапазоны: 1000, 2000, 3000...
        $n = 1;
        foreach (DB::table('categories')->orderBy('id')->pluck('id') as $id) {
            DB::table('categories')->where('id', $id)->update(['sku_start' => $n++ * 1000]);
        }
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('sku_start');
        });
    }
};
