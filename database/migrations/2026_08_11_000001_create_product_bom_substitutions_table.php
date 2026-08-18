<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aturan substitusi komponen — pengganti yang boleh dipakai kasir ketika komponen
 * normal pada satu baris BOM tidak mencukupi.
 *
 * Cakupannya PER BARIS BOM (bukan global per komponen), sehingga produk berbeda
 * boleh punya aturan berbeda untuk komponen yang sama.
 *
 * Contoh:
 *   product_bom: Bakso Urat membutuhkan Bakso Kecil × 3
 *   aturan     : boleh diganti Bakso Besar Urat × 2
 *
 * quantity = jumlah komponen pengganti per 1 unit produk, MENGGANTIKAN seluruh
 * qty baris BOM tersebut (bukan per satuan komponen asal).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_bom_substitutions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_bom_id')
                ->constrained('product_bom')
                ->cascadeOnDelete(); // Baris BOM hilang → aturannya ikut hilang
            $table->foreignId('component_id')
                ->constrained()
                ->restrictOnDelete(); // Komponen pengganti, jangan bisa dihapus saat dipakai
            $table->decimal('quantity', 12, 3); // Qty pengganti per 1 produk
            $table->string('label', 100)->nullable(); // Teks tombol opsional untuk kasir
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Satu baris BOM tidak boleh punya duplikat komponen pengganti
            $table->unique(['product_bom_id', 'component_id'], 'uq_bom_substitute');
            $table->index('product_bom_id');
            $table->index('component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_bom_substitutions');
    }
};
