<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spray_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->string('chemical_used');
            $table->string('target_pest')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->integer('pre_harvest_interval_days')->default(0);
            $table->foreignId('applicator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spray_logs');
    }
};
