<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Exactly which chemical or input to apply, and when. This is the row the
     * reminder engine reads: e.g. day 60, Mancozeb fungicide, "blight prevention".
     */
    public function up(): void
    {
        Schema::create('crop_cycle_schedule_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_cycle_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('day_offset');
            $table->string('activity_type'); // spray | foliar_feed | input | harvest_check
            $table->string('product_name')->nullable();
            $table->string('purpose')->nullable();
            $table->string('dosage')->nullable();
            $table->unsignedInteger('pre_harvest_interval_days')->nullable();
            $table->timestamps();

            $table->index(['crop_cycle_template_id', 'day_offset']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_cycle_schedule_points');
    }
};
