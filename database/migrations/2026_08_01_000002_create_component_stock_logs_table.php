<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail pergerakan stok komponen.
 *
 * Types:
 * - production_add  : Penambahan dari repacking/produksi
 * - bom_deduct      : Pengurangan dari checkout via BOM produk
 * - modifier_deduct : Pengurangan dari checkout via modifier
 * - return_add      : Pengembalian dari retur transaksi
 * - adjustment      : Penyesuaian manual oleh admin
 * - initial         : Stok awal saat komponen dibuat
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('component_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 50); // production_add, bom_deduct, modifier_deduct, return_add, adjustment, initial
            $table->decimal('amount', 12, 3);        // + bertambah, - berkurang
            $table->decimal('final_stock', 12, 3);   // stok setelah perubahan
            $table->text('note')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();   // production_id atau transaction_id
            $table->string('reference_type', 100)->nullable();         // 'production', 'transaction'
            $table->timestamps();

            $table->index('component_id');
            $table->index('type');
            $table->index('created_at');
            $table->index(['reference_id', 'reference_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_stock_logs');
    }
};
