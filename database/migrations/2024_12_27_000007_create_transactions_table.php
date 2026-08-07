<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreignId('shift_id')->nullable()->constrained();
            $table->foreignId('payment_source_id')->nullable()->constrained('payment_sources');
            // Status pembayaran dari gateway (raw status Midtrans). FK payment_transaction_id
            // ditambahkan belakangan (lihat alter_transactions_add_payment_gateway_columns)
            // karena tabel payment_transactions baru dibuat setelah tabel ini.
            $table->string('payment_gateway_status', 50)->nullable();

            $table->string('invoice_number', 50)->unique();
            $table->string('receipt_token', 64)->nullable()->unique();
            $table->integer('queue_number');
            $table->decimal('subtotal', 14, 2);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('change_amount', 14, 2)->default(0);
            $table->enum('payment_method', ['cash', 'qris', 'transfer', 'card', 'ewallet'])->default('cash');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->string('cancelled_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('customer_email', 150)->nullable();
            $table->enum('order_type', ['dine_in', 'take_away'])->default('dine_in');

            // Sumber transaksi: POS atau Self Order. FK self_order_id ditambahkan
            // belakangan (lihat alter_transactions_add_self_order_columns) karena
            // tabel self_orders baru dibuat setelah tabel ini.
            $table->enum('source', ['pos', 'self_order'])
                ->default('pos')
                ->comment('Sumber transaksi: POS atau Self Order');

            $table->text('notes')->nullable();
            $table->unsignedInteger('print_count')->default(0);
            $table->timestamps();

            // Heavy indexing for reports
            $table->index('invoice_number');
            $table->index('shift_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['shift_id', 'status']);
            $table->index('source');
        });

        // Shift expenses table (for operational costs, refunds)
        Schema::create('shift_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 14, 2);
            $table->enum('category', ['operational', 'refund', 'other'])->default('other');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shift_expenses');
        Schema::dropIfExists('transactions');
    }
};
