<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rk_anggotas', function (Blueprint $table) {
            if (!Schema::hasColumn('rk_anggotas', 'final_evidence')) {
                $table->text('final_evidence')->nullable()->after('approved_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rk_anggotas', function (Blueprint $table) {
            if (Schema::hasColumn('rk_anggotas', 'final_evidence')) {
                $table->dropColumn('final_evidence');
            }
        });
    }
};