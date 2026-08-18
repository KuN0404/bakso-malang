<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('supplier_name')
                ->constrained('suppliers')->nullOnDelete();
        });

        // Backfill: nomor supplier_name lama (bebas format) dipetakan ke baris Supplier
        // (dibuat jika belum ada), lalu isi supplier_id yang sesuai. Ditulis defensif
        // agar migrasi ini tetap aman dijalankan di lingkungan mana pun, meski saat ini
        // tabel purchases kosong.
        $distinctNames = DB::table('purchases')
            ->whereNotNull('supplier_name')
            ->where('supplier_name', '!=', '')
            ->distinct()
            ->pluck('supplier_name');

        foreach ($distinctNames as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $supplierId = DB::table('suppliers')->where('name', $name)->value('id');

            if (!$supplierId) {
                $supplierId = DB::table('suppliers')->insertGetId([
                    'name'       => $name,
                    'is_active'  => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('purchases')->where('supplier_name', $name)->update(['supplier_id' => $supplierId]);
        }

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropColumn('supplier_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->string('supplier_name', 150)->nullable()->after('purchase_date');
        });

        DB::table('purchases')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->update(['purchases.supplier_name' => DB::raw('suppliers.name')]);

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_id');
        });
    }
};
