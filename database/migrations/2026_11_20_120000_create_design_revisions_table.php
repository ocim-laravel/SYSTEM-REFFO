<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom mengikuti 05-data-model.md bagian D (design_revisions) untuk AC-03-4.
     */
    public function up(): void
    {
        Schema::create('design_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('design_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cycle_no');
            $table->text('request_note');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamps();

            $table->index('design_job_id');
            $table->unique(['design_job_id', 'cycle_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_revisions');
    }
};
