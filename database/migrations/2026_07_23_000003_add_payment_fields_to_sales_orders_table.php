<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Until now a sales order carried quantities but no receivable: its value
     * was only ever derived from its lines at render time, and nothing tracked
     * what the customer had actually paid. These columns make the order the
     * invoice — the record an M-Pesa payment (or a cash receipt) settles.
     *
     * `amount_repaid` already on this table is unrelated: that is money paid
     * back to the buyer for rejected/returned produce.
     */
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->string('invoice_number')->nullable()->unique()->after('id');
            // Snapshot of the line total at invoicing time. Kept as a column
            // rather than always recomputed so a receipt issued today still
            // reconciles if a line is corrected tomorrow.
            $table->decimal('total_amount', 12, 2)->default(0)->after('requested_quantity');
            $table->decimal('amount_paid', 12, 2)->default(0)->after('total_amount');
            $table->string('payment_status')->default('unpaid')->after('amount_paid'); // unpaid | partial | paid
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'total_amount', 'amount_paid', 'payment_status']);
        });
    }
};
