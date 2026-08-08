<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagers', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('pagers', function (Blueprint $table) {
            $table->dropUnique('pagers_number_unique');
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::table('pagers', function (Blueprint $table) {
            $table->dropIndex(['number']);
            $table->unique('number');
        });

        Schema::table('pagers', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
