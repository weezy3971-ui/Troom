<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Money received, and the receipt issued for it. One row per payment, so a
     * part-paid order yields a receipt per instalment — which is what the
     * customer expects to be handed.
     *
     * Payment and receipt are one table on purpose: a receipt only ever exists
     * because money arrived, and splitting them would let the two drift apart.
     * The row is immutable once written — a mistake is voided and re-receipted,
     * never edited, so a receipt number always means one fixed amount.
     *
     * `payable_type`/`payable_id` follows the reference_type/reference_id
     * convention already used by ledger_entries: today it points at a sales
     * order, and it will take horse rides and any later module unchanged.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('payable_type');
            $table->unsignedBigInteger('payable_id');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('method'); // see Payment::METHODS
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');
            // External reference for reconciliation: the M-Pesa receipt code,
            // cheque number, or bank slip reference.
            $table->string('reference')->nullable();
            $table->string('payer_phone')->nullable(); // 2547XXXXXXXX
            $table->text('notes')->nullable();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();

            // Voiding, rather than deleting or editing — the receipt number
            // stays spent so the sequence has no silent gaps.
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('void_reason')->nullable();

            // Trooms is not VAT-registered today, so receipts are plain
            // sequential documents. These stay null until (and unless) that
            // changes: retrofitting eTIMS onto an already-issued receipt series
            // is far more painful than carrying three unused columns.
            $table->string('etims_invoice_number')->nullable();
            $table->string('etims_control_unit_number')->nullable();
            $table->text('etims_qr')->nullable();

            $table->timestamps();

            $table->index(['payable_type', 'payable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
