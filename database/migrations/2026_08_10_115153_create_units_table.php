<?php

use App\Models\Unit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50);
            $table->string('symbol', 10)->unique();
            $table->string('group', 30);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('group');
            $table->index('is_active');
        });

        // Isi 33 satuan standar (Berat/Volume/Satuan/Kemasan) — idempoten, dipakai
        // juga oleh UnitSeeder supaya migrate saja & migrate:fresh --seed konsisten.
        Unit::seedDefaults();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
