<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Размер изделия в миллиметрах: длина, ширина, высота
        if (!Schema::hasColumn('products', 'size_length')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('size_length')->nullable();
                $table->unsignedInteger('size_width')->nullable();
                $table->unsignedInteger('size_height')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['size_length', 'size_width', 'size_height']);
        });
    }
};
