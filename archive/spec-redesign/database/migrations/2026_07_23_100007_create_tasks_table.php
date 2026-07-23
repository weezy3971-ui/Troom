<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Core-platform task list: polymorphic, so any module record (a block, a
     * cycle, later a horse or a cow) can raise work. Drives the reminder engine
     * and the field app's "today's tasks" list.
     */
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('module')->default('horticulture');
            $table->nullableMorphs('related');
            $table->string('description');
            $table->string('status')->default('pending'); // pending | done | cancelled
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('source')->default('manual'); // manual | schedule_point
            $table->foreignId('crop_cycle_schedule_point_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crop_cycle_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            // One task per schedule point per cycle — the guard that stops the
            // daily command raising the same reminder twice.
            $table->unique(['crop_cycle_id', 'crop_cycle_schedule_point_id'], 'tasks_cycle_point_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
