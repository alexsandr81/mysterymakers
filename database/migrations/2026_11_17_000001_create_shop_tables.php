<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_admin')->default(false)->after('phone');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('slug')->unique();
            $table->string('name_uk');
            $table->string('name_ru');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 50)->unique();
            $table->string('slug')->unique();
            $table->string('name_uk');
            $table->string('name_ru');
            $table->text('description_uk')->nullable();
            $table->text('description_ru')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('old_price', 10, 2)->nullable(); // если задана — показываем скидку
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable(); // массив путей в storage/app/public
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 20);
            $table->string('email')->nullable();
            $table->string('delivery', 30);
            $table->string('address')->nullable();
            $table->string('payment', 30);
            $table->text('comment')->nullable();
            $table->decimal('total', 10, 2);
            $table->string('status', 20)->default('new');
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name'); // название на момент покупки
            $table->decimal('price', 10, 2); // цена на момент покупки
            $table->unsignedInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'is_admin']);
        });
    }
};
