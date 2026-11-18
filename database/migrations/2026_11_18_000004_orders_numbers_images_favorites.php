<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Фото товара на момент заказа (чтобы не терялось, если товар удалят)
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('image')->nullable()->after('name');
        });

        // Номера заказов — только цифры: 1000 + id
        DB::table('orders')->orderBy('id')->each(function ($order) {
            DB::table('orders')->where('id', $order->id)->update(['number' => (string) (1000 + $order->id)]);
        });

        Schema::create('favorites', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['user_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('image');
        });
    }
};
