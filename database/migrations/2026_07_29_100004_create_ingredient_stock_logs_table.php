<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredient_stock_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type'); // purchase, production_use, adjustment, initial, sub
            $table->decimal('amount', 12, 3);
            $table->decimal('final_stock', 12, 3);
            $table->string('note')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredient_stock_logs');
    }
};
