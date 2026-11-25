<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Журнал действий модераторов: администратор видит, что и когда они меняли
        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('user_name');           // имя на момент действия (остаётся, даже если пользователя удалят)
            $table->string('label');                // что сделано: «Изменён товар»
            $table->string('subject')->nullable();  // над чем: название товара, категории…
            $table->string('url')->nullable();      // куда перейти (карточка товара в админке)
            $table->timestamp('created_at')->useCurrent()->index();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('activity_seen_at')->nullable(); // когда администратор последний раз открывал журнал
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('activity_seen_at'));
        Schema::dropIfExists('activity_log');
    }
};
