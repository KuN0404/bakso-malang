<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshot komposisi komponen yang BENAR-BENAR dipakai pada satu baris transaksi.
 *
 * Tanpa tabel ini, retur menghitung ulang dari BOM yang hidup saat ini — salah
 * untuk penjualan yang memakai substitusi (mengembalikan komponen yang tidak
 * pernah dipotong) maupun untuk BOM yang sudah diubah admin setelah penjualan.
 *
 * Contoh satu baris transaksi "1 × Bakso Urat" dengan substitusi
 * (3 Bakso Kecil → 2 Bakso Besar Urat):
 *   Bakso Besar Urat  per_unit 3  total 3  source 'substitute'  replaced: Bakso Kecil (3)
 *   Kuah Kaldu        per_unit 1  total 1  source 'bom'
 *   (Bakso Kecil tidak ditulis sama sekali — memang tidak dipotong)
 *
 * quantity_per_unit = komposisi yang DIMAKSUD, per 1 unit produk.
 * quantity_total    = yang BENAR-BENAR dikurangi dari stok. Pada jalur best-effort
 *                     settlement QRIS keduanya bisa berbeda (oversold), dan retur
 *                     WAJIB memakai quantity_total agar tidak mengembalikan lebih
 *                     banyak daripada yang pernah dipotong.
 *
 * Komponen milik MODIFIER sengaja TIDAK ditulis ke sini — pivot
 * transaction_detail_modifier sudah menjadi snapshot tersendiri dan sudah dipakai
 * restoreForReturn(). Menulis di dua tempat akan menyebabkan retur ganda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_detail_components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_detail_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('component_id')
                ->constrained()
                ->restrictOnDelete();
            $table->string('component_name', 150); // Snapshot nama, tahan rename komponen
            $table->decimal('quantity_per_unit', 12, 3);
            $table->decimal('quantity_total', 12, 3);
            $table->string('source', 20); // 'bom' | 'substitute'
            $table->foreignId('replaced_component_id')
                ->nullable()
                ->constrained('components')
                ->nullOnDelete();
            $table->decimal('replaced_quantity', 12, 3)->nullable();

            // SENGAJA tanpa foreign key: ini catatan historis yang tidak boleh berubah.
            // restrictOnDelete akan membuat baris BOM / aturan mustahil dihapus selamanya,
            // sedangkan cascadeOnDelete akan diam-diam merusak riwayat transaksi.
            $table->unsignedBigInteger('product_bom_id')->nullable();
            $table->unsignedBigInteger('substitution_rule_id')->nullable(); // null = substitusi manual kasir

            $table->timestamps();

            $table->index('transaction_detail_id');
            $table->index('component_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_detail_components');
    }
};
