<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Если успел появиться текстовый размер — переносим числа в новые поля и удаляем старое */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'size')) {
            return;
        }

        foreach (DB::table('products')->whereNotNull('size')->where('size', '!=', '')->get(['id', 'size']) as $row) {
            preg_match_all('/\d+/', $row->size, $m);
            $n = array_slice($m[0], 0, 3);
            DB::table('products')->where('id', $row->id)->update([
                'size_length' => $n[0] ?? null, 'size_width' => $n[1] ?? null, 'size_height' => $n[2] ?? null,
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('size');
        });
    }

    public function down(): void
    {
    }
};
