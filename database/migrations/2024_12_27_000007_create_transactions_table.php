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
            $table->foreignId('user_id')->constrained();
            $table->foreignId('shift_id')->nullable()->constrained();
            $table->foreignId('payment_source_id')->nullable()->constrained('payment_sources');
            $table->string('invoice_number', 50)->unique();
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
            $table->enum('order_type', ['dine_in', 'take_away'])->default('dine_in');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Heavy indexing for reports
            $table->index('invoice_number');
            $table->index('shift_id');
            $table->index('status');
            $table->index('payment_method');
            $table->index('created_at');
            $table->index(['status', 'created_at']);
            $table->index(['shift_id', 'status']);
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
