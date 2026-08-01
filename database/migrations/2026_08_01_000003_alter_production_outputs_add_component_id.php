<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah production_outputs agar mendukung output ke Component.
 *
 * - Tambah component_id (nullable) — output ke komponen setengah jadi
 * - Buat product_id nullable — backward compat untuk data lama
 *
 * Rule (enforced di aplikasi):
 *   Setiap baris harus memiliki SALAH SATU dari component_id atau product_id.
 *   Repacking baru hanya menggunakan component_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            // Tambah component_id setelah production_id
            $table->foreignId('component_id')
                ->nullable()
                ->after('production_id')
                ->constrained()
                ->cascadeOnDelete();

            // Ubah product_id menjadi nullable (backward compat)
            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
            $table->dropColumn('component_id');
            $table->foreignId('product_id')->nullable(false)->change();
        });
    }
};
