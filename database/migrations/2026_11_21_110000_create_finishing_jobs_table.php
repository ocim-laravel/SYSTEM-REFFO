<?php

use App\Enums\FinishingJobStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pekerjaan finishing per item (MOD-05, 05-data-model.md bagian F).
     */
    public function up(): void
    {
        Schema::create('finishing_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->nullable();
            $table->string('status', 32)->default(FinishingJobStatus::Queued->value);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->text('progress_note')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique('order_item_id');
            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finishing_jobs');
    }
};
