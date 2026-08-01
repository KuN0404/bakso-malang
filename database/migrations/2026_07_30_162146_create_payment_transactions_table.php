<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            // Referensi ke transaksi POS (diisi SETELAH pembayaran dikonfirmasi)
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();

            // Invoice number sebagai referensi utama saat transaction_id belum ada
            $table->string('invoice_number', 50)->index();

            // Midtrans order ID — unik per request ke Midtrans
            // Format: INV-YYYYMMDD-XXXX-{timestamp}
            $table->string('midtrans_order_id', 100)->unique();

            // QR code image URL dari Midtrans
            $table->text('qr_code_url')->nullable();

            // Metode pembayaran
            $table->string('payment_method', 20)->default('qris');

            // Nominal — harus cocok dengan total transaksi
            $table->decimal('amount', 14, 2);

            // State machine status
            $table->string('status', 30)->default('initiated')->index();

            // Raw response dari Midtrans untuk keperluan audit & debug
            $table->json('midtrans_response')->nullable();

            // Waktu-waktu penting
            $table->timestamp('webhook_received_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable()->index(); // Untuk job expire check

            // Idempotency key — mencegah duplikasi request
            $table->string('idempotency_key', 100)->unique()->nullable();

            // Kasir yang membuat
            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();

            // Composite indexes
            $table->index(['status', 'expired_at']);
            $table->index(['invoice_number', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
