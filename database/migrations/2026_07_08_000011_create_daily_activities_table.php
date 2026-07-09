<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_type'); // controlled list — see DailyActivity::TYPES
            $table->date('activity_date');
            $table->text('description')->nullable();
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->string('gps_location')->nullable(); // "lat,lng"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_activities');
    }
};
