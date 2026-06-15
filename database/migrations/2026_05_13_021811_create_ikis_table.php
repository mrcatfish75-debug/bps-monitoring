<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ikis', function (Blueprint $table) {
            $table->id();

            $table->foreignId('rk_anggota_id')
                ->constrained('rk_anggotas')
                ->cascadeOnDelete();

            $table->text('description');

            $table->string('target')->nullable();
            $table->string('unit')->nullable();

            $table->string('status')->default('draft');

            $table->text('final_evidence')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('rejection_note')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('rk_anggota_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ikis');
    }
};