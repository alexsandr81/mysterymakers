<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Таблица филамента для калькулятора была отменена: цена за грамм снова задаётся одним числом в параметрах расчёта.
    // Если таблица успела создаться — убираем; на чистой базе ничего не делает.
    public function up(): void
    {
        Schema::dropIfExists('calc_filaments');
    }

    public function down(): void
    {
    }
};
