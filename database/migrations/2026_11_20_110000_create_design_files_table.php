<?php

use App\Enums\DesignFileKind;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom mengikuti 05-data-model.md bagian D (design_files) untuk AC-03-3.
     */
    public function up(): void
    {
        Schema::create('design_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('design_job_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind', 16)->default(DesignFileKind::Draft->value);
            $table->string('path');
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('design_job_id');
            $table->unique(['design_job_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_files');
    }
};
