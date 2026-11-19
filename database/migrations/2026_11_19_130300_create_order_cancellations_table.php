<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pembatalan: biaya aktual dan nilai kesepakatan disimpan terpisah (BR-15).
     */
    public function up(): void
    {
        Schema::create('order_cancellations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->text('reason');
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->decimal('settlement_amount', 15, 2)->default(0);
            $table->text('settlement_note')->nullable();
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_cancellations');
    }
};
