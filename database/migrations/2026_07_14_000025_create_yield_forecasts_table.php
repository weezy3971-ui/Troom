<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pre-harvest yield sampling: walk a sample of beds, weigh their pick, and
        // extrapolate to the whole planting ("7 kg/bed × 100 beds = 700 kg"). Gives
        // a near-term harvest forecast to compare against what actually comes in.
        Schema::create('yield_forecasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->date('forecast_date');
            $table->integer('sample_bed_count');
            $table->integer('total_bed_count');
            $table->decimal('sample_yield_kg', 10, 2);
            $table->decimal('projected_total_kg', 12, 2); // derived: sample/bed × total beds
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('yield_forecasts');
    }
};
