<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->enum('paper_size', ['58mm', '80mm', 'custom'])->default('58mm');
            $table->integer('paper_width_px')->nullable(); // For custom size
            $table->boolean('is_default')->default(false);
            $table->boolean('auto_print')->default(true);
            $table->json('margins')->nullable(); // {top, right, bottom, left}
            $table->string('font_family')->default('monospace');
            $table->integer('font_size_px')->default(12);
            $table->boolean('show_logo')->default(true);
            $table->boolean('show_footer')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_configs');
    }
};
