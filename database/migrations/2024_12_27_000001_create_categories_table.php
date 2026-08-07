<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('target_kitchen', 20)->default('food');
            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
            $table->index('target_kitchen');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
