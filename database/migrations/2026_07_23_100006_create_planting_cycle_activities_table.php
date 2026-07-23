<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What was actually done against a cycle. A row with a schedule point is the
     * completion of a scheduled task; a null schedule point is an ad hoc,
     * unscheduled activity. cost_kes mirrors into an expense on save.
     */
    public function up(): void
    {
        Schema::create('planting_cycle_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_cycle_schedule_point_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type'); // spray | foliar_feed | input | harvest_check
            $table->string('product_name')->nullable();
            $table->date('performed_date');
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('dosage')->nullable();
            $table->decimal('cost_kes', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['crop_cycle_id', 'performed_date']);
            $table->index('crop_cycle_schedule_point_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planting_cycle_activities');
    }
};
