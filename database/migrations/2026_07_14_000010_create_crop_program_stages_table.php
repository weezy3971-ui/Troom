<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ordered stages within a program: what to do, how many days after
        // planting, and (optionally) how often it repeats ("6 to 7 sprays — every
        // week"; "feeding is like 3 times, second week, fourth week").
        Schema::create('crop_program_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('name');
            $table->string('activity_type')->nullable();
            $table->integer('offset_days')->default(0);
            $table->string('cadence')->nullable();       // e.g. "weekly ×7"
            $table->string('default_inputs')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_program_stages');
    }
};
