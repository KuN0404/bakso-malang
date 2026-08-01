<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('unit', 20)->default('kg'); // kg, gram, liter, ml, pcs, pack
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->text('note')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
