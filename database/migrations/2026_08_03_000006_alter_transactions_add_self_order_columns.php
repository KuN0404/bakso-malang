<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Sumber transaksi: POS atau Self Order
            $table->enum('source', ['pos', 'self_order'])
                ->default('pos')
                ->after('order_type')
                ->comment('Sumber transaksi: POS atau Self Order');

            // Referensi ke self order (nullable karena POS tidak punya self_order)
            $table->foreignId('self_order_id')
                ->nullable()
                ->after('source')
                ->constrained('self_orders')
                ->nullOnDelete();

            // Indexes
            $table->index('source');
            $table->index('self_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['self_order_id']);
            $table->dropIndex(['source']);
            $table->dropIndex(['self_order_id']);
            $table->dropColumn(['source', 'self_order_id']);
        });
    }
};
