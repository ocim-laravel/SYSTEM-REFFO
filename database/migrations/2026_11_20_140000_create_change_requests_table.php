<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom mengikuti user spec AC-03-6 (change_requests).
     */
    public function up(): void
    {
        Schema::create('change_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('design_file_id')->nullable()->constrained('design_files')->nullOnDelete();
            $table->text('reason');
            $table->decimal('cost_impact', 15, 2)->default(0);
            $table->timestamp('deadline_impact')->nullable();
            $table->string('status', 16)->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();

            $table->index('order_item_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
    }
};
