<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_tasks', 'iki_id')) {
                $table->foreignId('iki_id')
                    ->nullable()
                    ->after('rk_anggota_id')
                    ->constrained('ikis')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            if (Schema::hasColumn('daily_tasks', 'iki_id')) {
                $table->dropConstrainedForeignId('iki_id');
            }
        });
    }
};