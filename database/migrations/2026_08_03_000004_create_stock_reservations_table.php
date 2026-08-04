<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->id();

            // Polymorphic reference ke model yang mereservasi (SelfOrder, dll.)
            $table->string('reservable_type', 100)->comment('App\\Models\\SelfOrder');
            $table->unsignedBigInteger('reservable_id');

            // Apa yang direservasi
            $table->enum('item_type', ['product', 'component'])->comment('Jenis item yang direservasi');
            $table->unsignedBigInteger('item_id')->comment('product_id atau component_id');

            $table->decimal('quantity', 12, 4)->comment('Jumlah yang direservasi (float untuk komponen)');

            // Status reservation
            $table->enum('status', [
                'active',    // Aktif (stok sedang direservasi)
                'released',  // Dilepas (cancel/expired)
                'converted', // Dikonversi ke penjualan aktual (settlement)
            ])->default('active');

            // Timeout reservation
            $table->timestamp('expires_at')->comment('Waktu reservation expire (QRIS: sesuai QRIS expire, Cash: +30 menit)');
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['reservable_type', 'reservable_id'], 'idx_sr_reservable');
            $table->index(['item_type', 'item_id', 'status'], 'idx_sr_item');
            $table->index(['expires_at', 'status'], 'idx_sr_expires');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
