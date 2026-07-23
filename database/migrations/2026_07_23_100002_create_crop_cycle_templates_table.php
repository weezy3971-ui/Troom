<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The reusable planting-to-harvest plan, selected when a block starts a new
     * cycle. e.g. "Tomato (Roma), 90 days".
     */
    public function up(): void
    {
        Schema::create('crop_cycle_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->nullable()->constrained()->nullOnDelete();
            $table->string('crop_name');
            $table->string('variety')->nullable();
            $table->unsignedInteger('total_cycle_days');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crop_cycle_templates');
    }
};
