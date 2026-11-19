<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('city')->nullable()->after('address');
            $table->string('branch')->nullable()->after('city');          // отделение НП или индекс Укрпошты
            $table->string('np_city_ref', 64)->nullable()->after('branch');
            $table->string('np_warehouse_ref', 64)->nullable()->after('np_city_ref');
        });

        // «Наличными» заменено на «Наложенный платёж»
        DB::table('orders')->where('payment', 'cash')->update(['payment' => 'cod']);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['city', 'branch', 'np_city_ref', 'np_warehouse_ref']);
        });
    }
};
