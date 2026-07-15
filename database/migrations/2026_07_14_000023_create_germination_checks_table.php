<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Field germination assessments taken a few days after sowing ("germination
        // after five days was 80–90%"). Rate is derived from the sampled counts and
        // feeds the yield projection, replacing the crop-default assumption.
        Schema::create('germination_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->date('check_date');
            $table->integer('days_after_sowing')->nullable();
            $table->integer('sample_size');
            $table->integer('germinated_count');
            $table->decimal('germination_rate', 4, 3); // stored fraction, 0.900 = 90%
            $table->string('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('germination_checks');
    }
};
