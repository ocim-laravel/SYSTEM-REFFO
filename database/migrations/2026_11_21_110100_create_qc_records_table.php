<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hasil QC per pekerjaan finishing (MOD-05, 05-data-model.md bagian F).
     */
    public function up(): void
    {
        Schema::create('qc_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('finishing_job_id')->constrained()->cascadeOnDelete();
            $table->string('result', 16);
            $table->text('notes')->nullable();
            $table->foreignId('checked_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index('finishing_job_id');
            $table->index('result');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_records');
    }
};
