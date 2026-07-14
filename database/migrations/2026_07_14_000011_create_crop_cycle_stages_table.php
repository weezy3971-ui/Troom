<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The materialised schedule for one crop cycle: due dates derived from
        // planting_date + the program stage offset. Ticked off as the matching
        // work is done; drives the stage-due notification.
        Schema::create('crop_cycle_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_program_stage_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('name');
            $table->string('activity_type')->nullable();
            $table->date('due_date');
            $table->string('status')->default('pending'); // pending | done | skipped
            $table->timestamp('completed_at')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_cycle_stages');
    }
};
