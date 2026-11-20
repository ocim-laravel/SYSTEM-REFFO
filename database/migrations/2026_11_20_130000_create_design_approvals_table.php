<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom mengikuti 05-data-model.md bagian D (design_approvals) untuk AC-03-5.
     */
    public function up(): void
    {
        Schema::create('design_approvals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('design_job_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_file_id')->constrained('design_files')->cascadeOnDelete();
            $table->foreignId('approved_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('method', 16)->default('internal');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('design_job_id');
            $table->index('design_file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_approvals');
    }
};
