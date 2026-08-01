<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah component_id ke modifiers.
 *
 * Jika modifier dikaitkan ke component → setiap pemilihan modifier
 * di POS akan mengurangi stok komponen tersebut sebanyak qty modifier.
 *
 * Contoh:
 *   Modifier "Bakso Extra (+2.000)" → component_id = Bakso Kecil
 *   Kasir pilih modifier ini × 3 → Bakso Kecil.stock -= 3
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            $table->foreignId('component_id')
                ->nullable()
                ->after('sort_order')
                ->constrained()
                ->nullOnDelete(); // Jika komponen dihapus, putus link (bukan hapus modifier)
        });
    }

    public function down(): void
    {
        Schema::table('modifiers', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
            $table->dropColumn('component_id');
        });
    }
};
