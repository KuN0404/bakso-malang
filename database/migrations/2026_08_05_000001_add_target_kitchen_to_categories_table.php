<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->string('target_kitchen', 20)->default('food')->after('is_active');
            $table->index('target_kitchen');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropIndex(['target_kitchen']);
            $table->dropColumn('target_kitchen');
        });
    }
};
