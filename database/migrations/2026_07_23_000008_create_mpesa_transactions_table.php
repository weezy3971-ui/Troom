<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The system of record for every M-Pesa movement, in or out — mirrors what
     * Safaricom's Daraja API itself tracks, so the shape is ready for a real
     * integration to write into unchanged: whoever wires up the live API only
     * has to populate these columns, not invent a new table.
     */
    public function up(): void
    {
        Schema::create('mpesa_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('direction'); // c2b | b2c — see MpesaTransaction::DIRECTIONS
            $table->string('phone'); // 2547XXXXXXXX
            $table->decimal('amount', 12, 2);
            $table->string('account_reference')->nullable(); // what the payer typed at the paybill / our invoice number
            $table->string('mpesa_receipt_number')->nullable(); // Safaricom's own transaction code
            $table->string('checkout_request_id')->nullable(); // STK Push correlation id (future use)
            $table->string('conversation_id')->nullable(); // B2C correlation id
            $table->string('originator_conversation_id')->nullable();
            $table->string('status')->default('pending'); // pending | success | failed
            $table->string('result_description')->nullable();
            $table->json('raw_payload')->nullable(); // the full request/callback, kept for audit and dispute resolution
            $table->string('payable_type')->nullable();
            $table->unsignedBigInteger('payable_id')->nullable();
            $table->foreignId('initiated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
            $table->index(['direction', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpesa_transactions');
    }
};
