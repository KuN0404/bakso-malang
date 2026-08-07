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
 *
 * Migration ini juga menambahkan component_id ke `modifiers` dan
 * `production_outputs` (butuh dilakukan di sini, bukan di migration
 * create tabel masing-masing, karena components baru dibuat sekarang).
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

        // Jika modifier dikaitkan ke component → setiap pemilihan modifier
        // di POS akan mengurangi stok komponen tersebut sebanyak qty modifier.
        Schema::table('modifiers', function (Blueprint $table) {
            $table->foreignId('component_id')
                ->nullable()
                ->after('sort_order')
                ->constrained()
                ->nullOnDelete(); // Jika komponen dihapus, putus link (bukan hapus modifier)
        });

        // Ubah production_outputs agar mendukung output ke Component.
        // Rule (enforced di aplikasi): setiap baris harus memiliki SALAH SATU
        // dari component_id atau product_id. Repacking baru hanya pakai component_id.
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->foreignId('component_id')
                ->nullable()
                ->after('production_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('production_outputs', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
            $table->dropColumn('component_id');
            $table->foreignId('product_id')->nullable(false)->change();
        });

        Schema::table('modifiers', function (Blueprint $table) {
            $table->dropForeign(['component_id']);
            $table->dropColumn('component_id');
        });

        Schema::dropIfExists('components');
    }
};
