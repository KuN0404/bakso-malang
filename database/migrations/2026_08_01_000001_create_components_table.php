<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel komponen — item setengah jadi hasil repacking.
 * Contoh: Bakso Kecil, Bakso Besar, Kuah, Sambal, Es Batu, dll.
 *
 * Berbeda dari `ingredients` (bahan baku mentah),
 * komponen adalah hasil olahan yang siap digunakan dalam BOM produk.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('unit', 30)->default('pcs'); // pcs, porsi, gram, liter, dll
            $table->decimal('stock', 12, 3)->default(0);
            $table->decimal('minimum_stock', 12, 3)->default(0); // threshold low-stock warning
            $table->decimal('cost_price', 12, 2)->default(0);    // HPP per unit (weighted avg dari repacking)
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
        Schema::dropIfExists('components');
    }
};
