<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('np_is_postomat')->default(false);     // выбран почтомат, а не отделение
            $table->string('np_street_ref', 40)->nullable();       // адресная доставка: улица (идентификатор НП)
            $table->string('np_street', 255)->nullable();
            $table->string('np_building', 20)->nullable();
            $table->string('np_flat', 20)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['np_is_postomat', 'np_street_ref', 'np_street', 'np_building', 'np_flat']);
        });
    }
};
