<?php

use App\Enums\DeliveryStatus;
use App\Enums\DeliveryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jadwal pengiriman / pemasangan / ambil sendiri (MOD-06, 05-data-model.md bagian G).
     * Biaya disimpan sekali di level order lewat deliveries.fee (BR-14).
     */
    public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default(DeliveryType::Delivery->value);
            $table->dateTime('scheduled_at');
            $table->foreignId('scheduled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('crew')->nullable();
            $table->string('status', 32)->default(DeliveryStatus::Scheduled->value);
            $table->text('address')->nullable();
            $table->decimal('fee', 15, 2)->default(0);
            $table->dateTime('departed_at')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('scheduled_at');
            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deliveries');
    }
};
