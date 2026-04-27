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
    Schema::create('daily_tasks', function (Blueprint $table) {
        $table->id();

        $table->foreignId('rk_anggota_id')->constrained()->cascadeOnDelete();

        $table->date('date');

        $table->text('activity');
        $table->text('output')->nullable();

        $table->string('evidence_url')->nullable();

        // STATUS APPROVAL
        $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');

        // INFO APPROVAL
        $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
        $table->timestamp('approved_at')->nullable();
        $table->text('review_note')->nullable();

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_tasks');
    }
};
