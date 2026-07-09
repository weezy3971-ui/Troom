<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('labour_attendances', function (Blueprint $table) {
            $table->id();
            $table->date('attendance_date');
            $table->string('worker_name');
            $table->foreignId('block_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('crop_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('task');
            $table->decimal('hours_worked', 6, 2);
            $table->decimal('rate', 10, 2);
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('labour_attendances');
    }
};
