<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rk_anggota_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('rk_anggota_templates', 'description_hash')) {
                $table->string('description_hash', 64)
                    ->nullable()
                    ->after('description');
            }
        });

        /*
        |--------------------------------------------------------------------------
        | Isi hash untuk data lama
        |--------------------------------------------------------------------------
        | Kalau tabel sudah sempat berisi data sebelum kolom description_hash ada,
        | data lama tetap dibuatkan hash.
        |--------------------------------------------------------------------------
        */
        DB::table('rk_anggota_templates')
            ->whereNull('description_hash')
            ->orderBy('id')
            ->chunkById(100, function ($templates) {
                foreach ($templates as $template) {
                    $normalized = mb_strtolower(
                        preg_replace('/\s+/u', ' ', trim($template->description))
                    );

                    DB::table('rk_anggota_templates')
                        ->where('id', $template->id)
                        ->update([
                            'description_hash' => hash('sha256', $normalized),
                        ]);
                }
            });

        Schema::table('rk_anggota_templates', function (Blueprint $table) {
            $table->unique('description_hash');
        });
    }

    public function down(): void
    {
        Schema::table('rk_anggota_templates', function (Blueprint $table) {
            if (Schema::hasColumn('rk_anggota_templates', 'description_hash')) {
                $table->dropUnique(['description_hash']);
                $table->dropColumn('description_hash');
            }
        });
    }
};