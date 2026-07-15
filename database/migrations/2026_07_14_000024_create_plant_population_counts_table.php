<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Periodic stand counts ("scouting") through the cycle — the surviving plant
        // population as a percentage (70/80/90/100%), which drops as sprays and
        // fertigation take out plants. The latest count refines the projection.
        Schema::create('plant_population_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->date('count_date');
            $table->integer('days_after_planting')->nullable();
            $table->decimal('population_rate', 4, 3); // stored fraction, 0.850 = 85%
            $table->integer('sample_bed_count')->nullable();
            $table->integer('plants_counted')->nullable();
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_population_counts');
    }
};
