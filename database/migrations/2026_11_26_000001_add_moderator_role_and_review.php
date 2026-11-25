<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_moderator')->default(false)->after('is_admin'); // модератор: работает с товарами, но без цен и заказов
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('needs_review')->default(false)->index(); // изменён модератором, ждёт проверки администратором
            $table->unsignedBigInteger('modified_by')->nullable();     // кто из модераторов менял последним
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn(['needs_review', 'modified_by']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_moderator'));
    }
};
