<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Язык, на котором клиент оформил заказ — на нём же уходят письма
        Schema::table('orders', function (Blueprint $table) {
            $table->string('locale', 5)->default('uk')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
