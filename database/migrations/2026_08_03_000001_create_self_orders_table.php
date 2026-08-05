<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('self_orders', function (Blueprint $table) {
            $table->id();

            // Identitas & Tracking (untuk customer tanpa login)
            $table->string('order_token', 64)->unique()->comment('UUID untuk tracking customer tanpa login');
            $table->unsignedSmallInteger('queue_number')->nullable()->comment('Nomor antrian harian');
            $table->string('invoice_number', 30)->nullable()->comment('Diisi saat dikonversi ke Transaction POS');

            // Data Customer
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->string('customer_email', 150)->nullable();

            // Finansial — snapshot harga saat order dibuat
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Metadata Order
            $table->enum('order_type', ['dine_in', 'take_away'])->default('dine_in');
            $table->text('notes')->nullable();

            // Payment
            $table->enum('payment_method', ['qris', 'cash_on_counter'])->default('qris');

            // Status Lifecycle
            $table->enum('status', [
                'pending_payment',  // QRIS: menunggu pembayaran
                'waiting_payment',  // Bayar di Tempat: menunggu kasir
                'paid',             // Sudah dibayar, menunggu proses
                'processing',       // Sedang diproses dapur
                'ready',            // Siap diambil/disajikan
                'completed',        // Selesai
                'cancelled',        // Dibatalkan
                'expired',          // QRIS expired / timeout
            ])->default('pending_payment');

            // Relasi ke PaymentTransaction (QRIS)
            $table->foreignId('payment_transaction_id')->nullable()->constrained('payment_transactions')->nullOnDelete();

            // Relasi ke Transaction POS (setelah settlement)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Kasir yang mengambil/memproses order (OWNER)
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('Kasir yang klik "Ambil Pesanan" — menjadi owner');

            // Pembatalan
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancelled_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            // Timestamps proses
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('claimed_at')->nullable()->comment('Waktu kasir mengambil pesanan');
            $table->timestamp('processing_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Idempotency — cegah double submit dari frontend
            $table->string('idempotency_key', 64)->nullable()->unique();

            // Security & Audit
            $table->string('customer_ip', 45)->nullable();

            $table->timestamps();

            // Indexes untuk query dashboard kasir
            $table->index(['status', 'created_at']);
            $table->index(['payment_method', 'status', 'created_at']);
            $table->index('customer_phone');
            $table->index('processed_by');
            $table->index('shift_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('self_orders');
    }
};
