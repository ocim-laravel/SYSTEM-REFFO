<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->string('prefix', 8);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('next_value')->default(1);
            $table->timestamps();

            $table->unique(['prefix', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
