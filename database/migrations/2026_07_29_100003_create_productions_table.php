<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('production_code', 50)->unique();
            $table->date('production_date');
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->string('status', 20)->default('completed');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index('production_date');
        });

        Schema::create('production_inputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });

        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
        Schema::dropIfExists('production_inputs');
        Schema::dropIfExists('productions');
    }
};
