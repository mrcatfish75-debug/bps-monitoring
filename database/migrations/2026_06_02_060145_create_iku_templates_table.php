<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iku_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Optional, bisa untuk kategori nama template
            $table->text('description'); // Konten template RK
            $table->string('category')->nullable(); // Misal kategori template: "RK Pribadi", "Project"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iku_templates');
    }
};