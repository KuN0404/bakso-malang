<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_aggregations', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['hourly', 'daily', 'weekly', 'monthly']);
            $table->date('report_date');
            $table->integer('hour')->nullable(); // 0-23 for hourly
            $table->decimal('total_sales', 14, 2)->default(0);
            $table->integer('transaction_count')->default(0);
            $table->decimal('total_discount', 14, 2)->default(0);
            $table->json('payment_breakdown')->nullable();
            $table->json('category_breakdown')->nullable();
            $table->integer('cancelled_count')->default(0);
            $table->decimal('cancelled_amount', 14, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['type', 'report_date', 'hour']);
            $table->index(['type', 'report_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_aggregations');
    }
};
