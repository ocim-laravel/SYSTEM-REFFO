<?php

use App\Enums\PrintJobStatus;
use App\Enums\PrintQueueClass;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pekerjaan cetak per item (MOD-04, 05-data-model.md bagian E).
     */
    public function up(): void
    {
        Schema::create('print_jobs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->string('queue_class', 16)->default(PrintQueueClass::Regular->value);
            $table->string('status', 32)->default(PrintJobStatus::Queued->value);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('qty_planned', 15, 2)->default(0);
            $table->decimal('qty_actual', 15, 2)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->unique('order_item_id');
            $table->index('queue_class');
            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
