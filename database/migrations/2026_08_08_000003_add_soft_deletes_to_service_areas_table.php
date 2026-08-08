<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropUnique('service_areas_code_unique');
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropIndex(['code']);
            $table->unique('code');
        });

        Schema::table('service_areas', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
