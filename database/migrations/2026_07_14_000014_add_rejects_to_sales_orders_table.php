<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rejects / returns tracking ("out of 1000 kilos we need 950, then 50
        // you'll reject") so order fulfilment, reject-rate and amount repaid can
        // be monitored per customer over time.
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->decimal('delivered_quantity', 12, 2)->default(0)->after('requested_quantity');
            $table->decimal('rejected_quantity', 12, 2)->default(0)->after('delivered_quantity');
            $table->decimal('returned_quantity', 12, 2)->default(0)->after('rejected_quantity');
            $table->decimal('amount_repaid', 12, 2)->default(0)->after('returned_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn(['delivered_quantity', 'rejected_quantity', 'returned_quantity', 'amount_repaid']);
        });
    }
};
