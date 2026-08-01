<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_status_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->cascadeOnDelete();

            // State machine transition
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);

            // Siapa yang memicu perubahan status
            $table->enum('triggered_by', ['cashier', 'webhook', 'system', 'job']);

            // Kasir / user (nullable jika diproses oleh sistem)
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();

            // Catatan tambahan
            $table->text('note')->nullable();

            // Raw data dari Midtrans webhook (untuk audit)
            $table->json('metadata')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('payment_transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_logs');
    }
};
