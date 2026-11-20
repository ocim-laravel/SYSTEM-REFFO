<?php

use App\Enums\DesignJobStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom mengikuti 05-data-model.md bagian D (design_jobs) untuk AC-03-2.
     * Tabel lain (design_files, revisions, approvals, change_requests)
     * menyusul di AC terkait.
     */
    public function up(): void
    {
        Schema::create('design_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('designer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->string('status', 32)->default(DesignJobStatus::Assigned->value);
            $table->unsignedInteger('revision_cycle_used')->default(0);
            $table->timestamps();

            $table->index('order_item_id');
            $table->index('designer_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_jobs');
    }
};
