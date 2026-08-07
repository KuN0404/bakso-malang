<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagers', function (Blueprint $table) {
            $table->id();
            $table->string('number', 20)->unique();       // "01", "A12"
            $table->string('docking_number', 20)->nullable(); // Slot charging dock
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('pager_id')
                ->nullable()
                ->after('order_type')
                ->constrained('pagers')
                ->nullOnDelete();

            $table->index('pager_id');
        });

        Schema::table('self_orders', function (Blueprint $table) {
            $table->foreignId('pager_id')
                ->nullable()
                ->after('service_area_id')
                ->constrained('pagers')
                ->nullOnDelete();

            $table->index('pager_id');
        });
    }

    public function down(): void
    {
        Schema::table('self_orders', function (Blueprint $table) {
            $table->dropForeign(['pager_id']);
            $table->dropIndex(['pager_id']);
            $table->dropColumn('pager_id');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['pager_id']);
            $table->dropIndex(['pager_id']);
            $table->dropColumn('pager_id');
        });

        Schema::dropIfExists('pagers');
    }
};
