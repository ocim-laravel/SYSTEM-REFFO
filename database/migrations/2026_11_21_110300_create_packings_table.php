<?php

use App\Enums\PackingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengepakan item setelah QC lulus (MOD-05, 05-data-model.md bagian F).
     */
    public function up(): void
    {
        Schema::create('packings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('packed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('packed_at')->nullable();
            $table->text('note')->nullable();
            $table->string('status', 16)->default(PackingStatus::InProgress->value);
            $table->timestamps();

            $table->unique('order_item_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packings');
    }
};
