<?php

use App\Enums\ItemCategory;
use App\Enums\ItemStatus;
use App\Enums\Priority;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom mengikuti 05-data-model.md bagian C persis.
     * Sengaja TIDAK ada kolom `code` — penomoran item bukan lingkup PH-02.
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('seq')->default(1);
            $table->string('title');
            $table->string('category', 16)->default(ItemCategory::Printing->value);
            $table->json('specification')->nullable();
            $table->decimal('qty', 15, 2)->default(1);
            $table->string('unit', 32)->nullable();
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamp('deadline_at')->nullable();
            $table->string('priority', 16)->default(Priority::Normal->value);
            $table->string('status', 32)->default(ItemStatus::Draft->value);
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
