<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rk_ketuas', function (Blueprint $table) {

            // 🔥 Tambah user_id (owner RK Ketua)
            $table->foreignId('user_id')
                  ->after('iku_id')
                  ->constrained()
                  ->cascadeOnDelete();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rk_ketuas', function (Blueprint $table) {

            // 🔥 Drop foreign key dulu
            $table->dropForeign(['user_id']);

            // 🔥 Hapus column
            $table->dropColumn('user_id');

        });
    }
};