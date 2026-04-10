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
    Schema::create('projects', function (Blueprint $table) {
        $table->id();
        $table->string('name');

        $table->foreignId('iku_id')->constrained()->cascadeOnDelete();
        $table->foreignId('team_id')->constrained()->cascadeOnDelete();
        $table->foreignId('leader_id')->constrained('users')->cascadeOnDelete();

        $table->date('start_date')->nullable();
        $table->date('end_date')->nullable();

        $table->enum('status', ['planning', 'ongoing', 'done'])->default('planning');

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
