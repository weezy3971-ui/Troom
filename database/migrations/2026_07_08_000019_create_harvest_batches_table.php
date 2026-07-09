<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->date('harvest_date');
            $table->decimal('quantity_kg', 12, 2);
            $table->string('quality_grade')->nullable(); // Grade A/B/C
            $table->decimal('rejects_kg', 12, 2)->default(0);
            $table->foreignId('harvested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_batches');
    }
};
