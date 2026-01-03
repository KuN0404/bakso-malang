<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('transaction_detail_id')->constrained()->nullOnDelete();
            $table->json('modifiers')->nullable()->after('product_name');
        });
    }

    public function down(): void
    {
        Schema::table('return_items', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn(['product_id', 'modifiers']);
        });
    }
};
