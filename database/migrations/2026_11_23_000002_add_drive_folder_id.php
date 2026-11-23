<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['categories', 'products'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->string('drive_folder_id')->nullable(); // id папки на Google Диске
            });
        }
    }

    public function down(): void
    {
        foreach (['categories', 'products'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropColumn('drive_folder_id');
            });
        }
    }
};
