<?php

use App\Enums\RedoStage;
use App\Enums\ReworkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pekerjaan ulang setelah QC gagal (MOD-05, 05-data-model.md bagian F).
     */
    public function up(): void
    {
        Schema::create('reworks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('qc_record_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('finishing_job_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason');
            $table->string('redo_stage', 16)->default(RedoStage::Finishing->value);
            $table->string('status', 16)->default(ReworkStatus::Pending->value);
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();

            $table->index('order_item_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reworks');
    }
};
