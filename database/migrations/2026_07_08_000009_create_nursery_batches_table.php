<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nursery_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete();
            $table->date('sow_date');
            $table->date('expected_ready_date')->nullable();
            $table->integer('quantity');
            $table->string('status')->default('sown'); // sown, growing, ready, transplanted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nursery_batches');
    }
};
