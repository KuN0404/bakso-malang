<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds three columns to separate non-cash (QRIS/Transfer/EDC) tracking from
     * cash tracking during shift closing.
     */
    public function up(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            // Expected non-cash from system records (auto-calculated at shift close)
            $table->decimal('expected_non_cash', 14, 2)->default(0)->after('cash_difference');
            // Actual non-cash verified by cashier (bank/QRIS statement)
            $table->decimal('actual_non_cash', 14, 2)->nullable()->after('expected_non_cash');
            // Difference: actual_non_cash - expected_non_cash
            $table->decimal('non_cash_difference', 14, 2)->nullable()->after('actual_non_cash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn(['expected_non_cash', 'actual_non_cash', 'non_cash_difference']);
        });
    }
};
