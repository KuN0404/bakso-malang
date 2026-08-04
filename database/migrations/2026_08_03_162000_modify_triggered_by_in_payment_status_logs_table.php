<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_status_logs', function (Blueprint $table) {
            $table->string('triggered_by', 50)->default('system')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payment_status_logs', function (Blueprint $table) {
            $table->enum('triggered_by', ['cashier', 'webhook', 'system', 'job'])->change();
        });
    }
};
