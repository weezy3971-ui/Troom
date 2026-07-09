<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farm_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type'); // pump, vehicle, equipment
            $table->date('purchase_date')->nullable();
            $table->string('status')->default('operational'); // operational, maintenance, down
            $table->decimal('current_hours', 10, 2)->default(0);
            $table->decimal('current_mileage', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
