<?php

use App\Enums\ReprintStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengajuan cetak ulang (MOD-04, BR-11).
     */
    public function up(): void
    {
        Schema::create('reprint_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('print_job_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty', 15, 2);
            $table->text('reason');
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->string('status', 16)->default(ReprintStatus::Pending->value);
            $table->decimal('actual_qty', 15, 2)->nullable();
            $table->timestamps();

            $table->index('print_job_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reprint_requests');
    }
};
