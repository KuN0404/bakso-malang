<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel ini menggantikan Cache untuk menyimpan cart data secara persisten.
        // Kritical: jika cache expire sebelum webhook Midtrans datang, 
        // data cart tetap aman di sini.
        Schema::create('self_order_cart_snapshots', function (Blueprint $table) {
            $table->id();
            $table->string('midtrans_order_id', 100)->unique();
            $table->foreignId('self_order_id')->constrained('self_orders')->cascadeOnDelete();
            $table->json('cart_data')->comment('Seluruh data cart ter-serialisasi untuk pemrosesan webhook');
            $table->timestamp('expires_at');
            $table->timestamp('created_at')->nullable();

            $table->index('midtrans_order_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_order_cart_snapshots');
    }
};
