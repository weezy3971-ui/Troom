<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_snapshots', function (Blueprint $table) {
            $table->id();
            $table->date('snapshot_date');
            $table->string('key'); // harvest_today, yield_per_acre, revenue, cost_per_kg, ...
            $table->decimal('value', 16, 2)->default(0);
            $table->string('unit')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['snapshot_date', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_snapshots');
    }
};
