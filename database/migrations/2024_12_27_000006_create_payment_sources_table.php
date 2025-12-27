<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_sources', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100); // Cash, QRIS BCA, Transfer BRI, dll
            $table->enum('type', ['cash', 'qris', 'transfer', 'card', 'ewallet']);
            $table->string('account_number')->nullable();
            $table->string('account_name')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_sources');
    }
};
