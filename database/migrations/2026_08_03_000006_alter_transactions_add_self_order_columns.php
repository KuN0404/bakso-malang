<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * self_order_id butuh FK ke self_orders, tabel yang baru dibuat di
     * migration ini (kolom `source` sudah ada sejak create_transactions_table).
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('self_order_id')
                ->nullable()
                ->after('source')
                ->constrained('self_orders')
                ->nullOnDelete();

            $table->index('self_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['self_order_id']);
            $table->dropIndex(['self_order_id']);
            $table->dropColumn('self_order_id');
        });
    }
};
