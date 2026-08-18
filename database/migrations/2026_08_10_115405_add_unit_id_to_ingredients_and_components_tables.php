<?php

use App\Models\Unit;
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
        // Defensif: pastikan 33 satuan standar sudah ada sebelum backfill,
        // idempoten jadi aman dipanggil ulang meski migrasi create_units_table
        // sudah menjalankannya juga.
        Unit::seedDefaults();

        Schema::table('ingredients', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('unit')
                ->constrained('units')->nullOnDelete();
        });

        Schema::table('components', function (Blueprint $table) {
            $table->foreignId('unit_id')->nullable()->after('unit')
                ->constrained('units')->nullOnDelete();
        });

        $this->backfillUnitId('ingredients');
        $this->backfillUnitId('components');

        // Verifikasi keras: tidak boleh ada baris yang tertinggal tanpa unit_id
        // sebelum kolom string `unit` yang lama dihapus — data "Daging Sapi" (unit='kg')
        // yang sudah ada di production/dev harus benar-benar ter-mapping dulu.
        $unmatchedIngredients = DB::table('ingredients')->whereNull('unit_id')->count();
        $unmatchedComponents = DB::table('components')->whereNull('unit_id')->count();

        if ($unmatchedIngredients > 0 || $unmatchedComponents > 0) {
            throw new \RuntimeException(
                "Backfill unit_id gagal total: {$unmatchedIngredients} ingredients dan {$unmatchedComponents} components masih NULL. " .
                'Migrasi dihentikan sebelum kolom unit lama dihapus supaya data tidak hilang.'
            );
        }

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropColumn('unit');
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropColumn('unit');
        });
    }

    /**
     * Cocokkan nilai string `unit` lama ke units.symbol. Jika ada nilai lama yang
     * tidak cocok dengan 33 satuan standar, buat baris Unit ad-hoc untuk simbol
     * tersebut alih-alih membiarkan datanya hilang jadi NULL.
     */
    private function backfillUnitId(string $table): void
    {
        $rows = DB::table($table)->whereNotNull('unit')->where('unit', '!=', '')->get(['id', 'unit']);

        foreach ($rows as $row) {
            $symbol = trim($row->unit);
            if ($symbol === '') {
                continue;
            }

            $unitId = DB::table('units')->where('symbol', $symbol)->value('id');

            if (!$unitId) {
                $unitId = DB::table('units')->insertGetId([
                    'name' => $symbol,
                    'symbol' => $symbol,
                    'group' => 'Satuan / Jumlah',
                    'sort_order' => 999,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table($table)->where('id', $row->id)->update(['unit_id' => $unitId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ingredients', function (Blueprint $table) {
            $table->string('unit', 20)->default('kg')->after('name');
        });

        Schema::table('components', function (Blueprint $table) {
            $table->string('unit', 30)->default('pcs')->after('name');
        });

        DB::table('ingredients')
            ->join('units', 'units.id', '=', 'ingredients.unit_id')
            ->update(['ingredients.unit' => DB::raw('units.symbol')]);

        DB::table('components')
            ->join('units', 'units.id', '=', 'components.unit_id')
            ->update(['components.unit' => DB::raw('units.symbol')]);

        Schema::table('ingredients', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });

        Schema::table('components', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_id');
        });
    }
};
