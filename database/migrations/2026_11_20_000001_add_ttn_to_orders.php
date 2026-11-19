<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('np_ttn', 30)->nullable();       // номер накладной
            $table->string('np_ttn_ref', 40)->nullable();   // идентификатор в API
            $table->string('np_status', 255)->nullable();   // последний известный статус посылки
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['np_ttn', 'np_ttn_ref', 'np_status']);
        });
    }
};
