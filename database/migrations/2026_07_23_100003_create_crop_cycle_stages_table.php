<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Growth stages within a template, expressed as day offsets from planting.
     * e.g. "Fruiting, day 55-75".
     */
    public function up(): void
    {
        Schema::create('crop_cycle_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_template_id')->constrained()->cascadeOnDelete();
            $table->string('stage_name');
            $table->unsignedInteger('start_day_offset');
            $table->unsignedInteger('end_day_offset');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['crop_cycle_template_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_cycle_stages');
    }
};
