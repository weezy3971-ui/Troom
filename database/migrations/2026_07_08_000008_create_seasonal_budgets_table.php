<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasonal_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->decimal('labour_budget', 12, 2)->default(0);
            $table->decimal('input_budget', 12, 2)->default(0);
            $table->decimal('irrigation_budget', 12, 2)->default(0);
            $table->decimal('overhead_budget', 12, 2)->default(0);
            $table->decimal('total_budget', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_budgets');
    }
};
