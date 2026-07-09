<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plantings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nursery_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity');
            $table->date('planting_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantings');
    }
};
