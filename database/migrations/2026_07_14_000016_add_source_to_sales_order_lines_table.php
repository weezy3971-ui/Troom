<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A line is sourced either from an in-house packhouse lot (default) or
        // topped up from an outgrower. packhouse_lot_id is already nullable.
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->string('source')->default('lot')->after('packhouse_lot_id'); // lot | outgrower
            $table->foreignId('outgrower_id')->nullable()->after('source')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('outgrower_id');
            $table->dropColumn('source');
        });
    }
};
