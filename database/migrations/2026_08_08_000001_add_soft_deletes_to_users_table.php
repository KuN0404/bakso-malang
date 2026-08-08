<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the plain unique constraints on username/email and replace them
     * with regular indexes, because soft-deleted rows keep occupying the
     * unique value at the DB level. Uniqueness among active (non-trashed)
     * users is instead enforced at the validation layer, scoped to
     * `deleted_at IS NULL`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropUnique('users_email_unique');
            $table->index('username');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['username']);
            $table->dropIndex(['email']);
            $table->unique('username');
            $table->unique('email');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
