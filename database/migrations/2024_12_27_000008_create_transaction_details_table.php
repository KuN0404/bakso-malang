<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name'); // Snapshot
            $table->decimal('unit_price', 12, 2);
            $table->integer('quantity');
            $table->decimal('modifier_total', 12, 2)->default(0);
            $table->decimal('subtotal', 14, 2);
            $table->string('status')->default('pending');
            $table->timestamp('fulfilled_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
            $table->index('product_id');
        });

        // Junction for transaction_details <-> modifiers
        Schema::create('transaction_detail_modifier', function (Blueprint $table) {
            $table->foreignId('transaction_detail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modifier_id')->constrained();
            $table->string('modifier_name'); // Snapshot
            $table->decimal('price_adjustment', 12, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->primary(['transaction_detail_id', 'modifier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_detail_modifier');
        Schema::dropIfExists('transaction_details');
    }
};
