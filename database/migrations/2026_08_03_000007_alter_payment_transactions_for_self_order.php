<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            // created_by sekarang nullable karena Self Order tidak memiliki kasir
            // (kasir baru assign saat "claim order")
            $table->unsignedBigInteger('created_by')->nullable()->change();

            // Sumber transaksi pembayaran
            $table->enum('source', ['pos', 'self_order'])
                ->default('pos')
                ->after('created_by')
                ->comment('Sumber: POS atau Self Order');

            // Referensi ke self_order
            $table->foreignId('self_order_id')
                ->nullable()
                ->after('source')
                ->constrained('self_orders')
                ->nullOnDelete();

            $table->index('source');
            $table->index('self_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table) {
            $table->dropForeign(['self_order_id']);
            $table->dropIndex(['source']);
            $table->dropIndex(['self_order_id']);
            $table->dropColumn(['source', 'self_order_id']);
            // Kembalikan created_by ke NOT NULL (hati-hati jika ada data null)
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });
    }
};
