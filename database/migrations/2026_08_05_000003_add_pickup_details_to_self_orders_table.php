<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('self_orders', function (Blueprint $table) {
            $table->string('pickup_name', 100)->nullable()->after('customer_phone')->comment('Nama pengambil pesanan bawa pulang');
            $table->string('pickup_phone', 20)->nullable()->after('pickup_name')->comment('No. HP pengambil pesanan bawa pulang');
            $table->timestamp('pickup_confirmed_at')->nullable()->after('completed_at')->comment('Waktu konfirmasi penyerahan pesanan bawa pulang');
        });
    }

    public function down(): void
    {
        Schema::table('self_orders', function (Blueprint $table) {
            $table->dropColumn(['pickup_name', 'pickup_phone', 'pickup_confirmed_at']);
        });
    }
};
