<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('packhouse_lot_id')->constrained()->cascadeOnDelete();
            $table->date('check_date');
            $table->json('parameters')->nullable();
            $table->string('result'); // pass, fail
            $table->foreignId('inspector_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('photo_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quality_checks');
    }
};
