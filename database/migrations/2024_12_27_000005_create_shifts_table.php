<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->decimal('opening_cash', 14, 2)->default(0);
            $table->decimal('expected_cash', 14, 2)->default(0);
            $table->decimal('actual_cash', 14, 2)->nullable();
            $table->decimal('cash_difference', 14, 2)->nullable();
            $table->decimal('expected_non_cash', 14, 2)->default(0);
            $table->decimal('actual_non_cash', 14, 2)->nullable();
            $table->decimal('non_cash_difference', 14, 2)->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
