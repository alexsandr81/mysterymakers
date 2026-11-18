<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Статические страницы: «Доставка», «Контакты», «О нас» и любые другие
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('title_uk');
            $table->string('title_ru');
            $table->text('body_uk')->nullable();
            $table->text('body_ru')->nullable();
            $table->boolean('is_published')->default(true);
            $table->boolean('show_in_footer')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Баннеры в слайдере на главной
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title_uk')->nullable();
            $table->string('title_ru')->nullable();
            $table->string('subtitle_uk')->nullable();
            $table->string('subtitle_ru')->nullable();
            $table->string('image'); // путь в storage/app/public
            $table->string('url')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Характеристики («Материал», «Цвет», «Размер»...) и их значения
        Schema::create('characteristics', function (Blueprint $table) {
            $table->id();
            $table->string('name_uk');
            $table->string('name_ru');
            $table->boolean('is_filterable')->default(true); // показывать фильтром в каталоге
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('characteristic_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('characteristic_id')->constrained()->cascadeOnDelete();
            $table->string('value_uk');
            $table->string('value_ru');
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });

        // Какие значения выбраны у какого товара
        Schema::create('characteristic_value_product', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('characteristic_value_id')->constrained('characteristic_values')->cascadeOnDelete();
            $table->primary(['product_id', 'characteristic_value_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characteristic_value_product');
        Schema::dropIfExists('characteristic_values');
        Schema::dropIfExists('characteristics');
        Schema::dropIfExists('banners');
        Schema::dropIfExists('pages');
    }
};
