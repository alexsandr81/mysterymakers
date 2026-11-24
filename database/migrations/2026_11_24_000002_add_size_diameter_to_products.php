<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'size_diameter')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('size_diameter')->nullable(); // диаметр круглого изделия, мм
            });
        }
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size_diameter');
        });
    }
};
