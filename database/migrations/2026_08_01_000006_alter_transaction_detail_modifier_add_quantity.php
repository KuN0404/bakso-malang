<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambah kolom quantity ke pivot transaction_detail_modifier.
 *
 * Sebelumnya modifier hanya dicatat nama + harga per item.
 * Sekarang quantity dapat diatur kasir (misal: Bakso Extra × 3).
 *
 * Perhitungan:
 *   total_modifier_harga = price_adjustment × quantity
 *
 * Default = 1 agar backward compat dengan data transaksi lama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_detail_modifier', function (Blueprint $table) {
            $table->unsignedSmallInteger('quantity')
                ->default(1)
                ->after('price_adjustment');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_detail_modifier', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
