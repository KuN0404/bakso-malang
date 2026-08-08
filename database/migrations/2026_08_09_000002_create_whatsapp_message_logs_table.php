<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_message_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('phone', 20);
            $table->text('message');
            $table->enum('status', ['sent', 'failed', 'blocked'])->default('sent');
            $table->string('reason')->nullable();
            $table->string('fonnte_message_id')->nullable();
            $table->string('fonnte_status')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('fonnte_message_id');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_message_logs');
    }
};
