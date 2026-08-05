<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_orders', function (Blueprint $table) {
            $table->foreignId('service_area_id')
                ->nullable()
                ->after('order_type')
                ->constrained('service_areas')
                ->nullOnDelete();

            $table->index('service_area_id');
        });
    }

    public function down(): void
    {
        Schema::table('self_orders', function (Blueprint $table) {
            $table->dropForeign(['service_area_id']);
            $table->dropIndex(['service_area_id']);
            $table->dropColumn('service_area_id');
        });
    }
};
