<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);           // "Meja 1", "VIP Room"
            $table->string('code', 20)->unique(); // "M1", "VIP1" 
            $table->string('description')->nullable();
            $table->enum('type', ['table', 'room', 'zone', 'other'])->default('table');
            $table->integer('capacity')->default(4);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index('sort_order');
        });

        // Add service_area_id to transactions table
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('service_area_id')->nullable()->after('order_type')->constrained('service_areas')->nullOnDelete();
            $table->index('service_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['service_area_id']);
            $table->dropColumn('service_area_id');
        });
        
        Schema::dropIfExists('service_areas');
    }
};
