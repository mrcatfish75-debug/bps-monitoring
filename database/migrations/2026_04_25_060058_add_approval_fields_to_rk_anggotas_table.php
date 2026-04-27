<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rk_anggotas', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])
                ->default('draft')
                ->after('description');

            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->timestamp('approved_at')->nullable()->after('submitted_at');

            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('final_evidence')->nullable()->after('approved_by');
            $table->text('rejection_note')->nullable()->after('final_evidence');
        });
    }

    public function down(): void
    {
        Schema::table('rk_anggotas', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);

            $table->dropColumn([
                'status',
                'submitted_at',
                'approved_at',
                'approved_by',
                'final_evidence',
                'rejection_note',
            ]);
        });
    }
};