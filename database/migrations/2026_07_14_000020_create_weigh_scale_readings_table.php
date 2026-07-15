<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Feed from the digital weighing scale: each notification records who
        // weighed, what was weighed and the weight. Rows arrive via the device
        // ingest endpoint (source = device) or can be entered manually.
        Schema::create('weigh_scale_readings', function (Blueprint $table) {
            $table->id();
            $table->string('device_name')->nullable();
            $table->string('external_id')->nullable();       // device's own id, for idempotency
            $table->foreignId('weighed_by_worker_id')->nullable()->constrained('workers')->nullOnDelete();
            $table->string('weighed_by_name');               // who weighed (operator)
            $table->string('item');                          // what was weighed
            $table->decimal('weight', 12, 3);
            $table->string('unit')->default('kg');
            $table->timestamp('weighed_at');
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crop_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('device');     // device | manual
            $table->string('notes')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('acknowledged_at');
            $table->index(['device_name', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weigh_scale_readings');
    }
};
