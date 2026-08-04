<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('self_order_id')->constrained('self_orders')->cascadeOnDelete();

            // Snapshot produk (integrity historis — harga tidak berubah meski admin ubah)
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->string('product_name', 200)->comment('Snapshot nama produk saat order dibuat');
            $table->decimal('unit_price', 12, 2)->comment('Snapshot harga satuan saat order dibuat');

            $table->unsignedSmallInteger('quantity')->default(1);
            $table->decimal('modifier_total', 12, 2)->default(0)->comment('Total harga modifier per unit');
            $table->decimal('subtotal', 12, 2)->default(0)->comment('(unit_price + modifier_total) × quantity');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('self_order_id');
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_order_items');
    }
};
