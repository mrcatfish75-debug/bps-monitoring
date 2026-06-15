<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (!Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')
                    ->nullable()
                    ->after('leader_id');
            }

            if (!Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')
                    ->nullable()
                    ->after('start_date');
            }

            if (!Schema::hasColumn('projects', 'status')) {
                $table->string('status')
                    ->default('active')
                    ->after('end_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if (Schema::hasColumn('projects', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('projects', 'end_date')) {
                $table->dropColumn('end_date');
            }

            if (Schema::hasColumn('projects', 'start_date')) {
                $table->dropColumn('start_date');
            }
        });
    }
};