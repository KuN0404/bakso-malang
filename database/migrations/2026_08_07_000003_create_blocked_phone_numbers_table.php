<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocked_phone_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->boolean('is_blocked')->default(true);
            $table->boolean('auto_blocked')->default(false)->comment('True jika diblokir otomatis oleh sistem anti-spam');
            $table->string('reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('unblocked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unblocked_at')->nullable();
            $table->timestamps();

            $table->index('is_blocked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocked_phone_numbers');
    }
};
