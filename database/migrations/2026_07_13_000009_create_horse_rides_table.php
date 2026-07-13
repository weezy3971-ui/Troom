<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('horse_rides', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('customer_name');
            $table->string('customer_phone')->nullable();
            $table->foreignId('horse_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('guide_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('start_time');
            $table->unsignedInteger('duration_minutes');
            $table->dateTime('end_time');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('payment_status')->default('paid'); // paid, unpaid
            // pending_assignment -> assigned -> completed (or cancelled)
            $table->string('status')->default('pending_assignment');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('horse_rides');
    }
};
