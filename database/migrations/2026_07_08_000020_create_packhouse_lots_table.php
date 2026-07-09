<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packhouse_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('harvest_batch_id')->constrained()->cascadeOnDelete();
            $table->string('lot_number');
            $table->date('pack_date');
            $table->decimal('quantity_packed', 12, 2);
            $table->string('packaging_type')->nullable();
            $table->string('traceability_code')->unique(); // immutable once generated
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packhouse_lots');
    }
};
