<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bill of Materials (BOM) — komposisi komponen untuk setiap produk.
 *
 * Contoh:
 *   Bakso Biasa (product_id=1) membutuhkan:
 *     - Bakso Kecil (component_id=1) × 3 pcs
 *     - Bakso Besar (component_id=2) × 1 pcs
 *     - Kuah        (component_id=3) × 1 porsi
 *
 * Jika produk memiliki BOM → checkout mengurangi stok komponen.
 * Jika produk TIDAK memiliki BOM → checkout mengurangi stok produk (backward compat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_bom', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained()
                ->restrictOnDelete(); // Jangan hapus komponen yang masih dipakai BOM
            $table->decimal('quantity', 12, 3); // Berapa unit komponen per 1 produk

            $table->timestamps();

            // Satu produk tidak boleh punya duplikat komponen
            $table->unique(['product_id', 'component_id'], 'uq_product_component');
            $table->index('product_id');
            $table->index('component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bom');
    }
};
