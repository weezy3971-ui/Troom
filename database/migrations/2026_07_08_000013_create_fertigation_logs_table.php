<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fertigation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('crop_cycle_id')->constrained()->cascadeOnDelete();
            $table->date('log_date');
            $table->string('nutrient_type');
            $table->decimal('quantity', 10, 2);
            $table->string('method')->nullable(); // drip, foliar, manual
            $table->decimal('cost', 12, 2)->default(0);
            $table->foreignId('logged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fertigation_logs');
    }
};
