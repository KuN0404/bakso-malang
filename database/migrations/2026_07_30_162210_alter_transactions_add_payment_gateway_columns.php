<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Relasi ke payment_transaction (diisi setelah gateway mengkonfirmasi)
            $table->foreignId('payment_transaction_id')
                ->nullable()
                ->after('payment_source_id')
                ->constrained('payment_transactions')
                ->nullOnDelete();

            // Status pembayaran dari gateway (raw status Midtrans)
            $table->string('payment_gateway_status', 50)
                ->nullable()
                ->after('payment_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['payment_transaction_id']);
            $table->dropColumn(['payment_transaction_id', 'payment_gateway_status']);
        });
    }
};
