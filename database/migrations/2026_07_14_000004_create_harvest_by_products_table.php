<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Secondary produce recovered alongside the main harvest (e.g. "mofefe"
        // leaves/offcuts) — tracked separately from the saleable main quantity.
        Schema::create('harvest_by_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('harvest_batch_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity_kg', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_by_products');
    }
};
