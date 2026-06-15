<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'password_changed_at')) {
                $table->timestamp('password_changed_at')
                    ->nullable()
                    ->after('is_default_password');
            }

            if (!Schema::hasColumn('users', 'password_reset_at')) {
                $table->timestamp('password_reset_at')
                    ->nullable()
                    ->after('password_changed_at');
            }

            if (!Schema::hasColumn('users', 'password_reset_by')) {
                $table->foreignId('password_reset_by')
                    ->nullable()
                    ->after('password_reset_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'password_reset_by')) {
                $table->dropConstrainedForeignId('password_reset_by');
            }

            if (Schema::hasColumn('users', 'password_reset_at')) {
                $table->dropColumn('password_reset_at');
            }

            if (Schema::hasColumn('users', 'password_changed_at')) {
                $table->dropColumn('password_changed_at');
            }
        });
    }
};