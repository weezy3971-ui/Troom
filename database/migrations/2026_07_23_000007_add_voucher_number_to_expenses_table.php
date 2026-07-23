<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A payment voucher is Trooms's own proof that a vendor was paid — the
     * outbound mirror of the customer receipt in `payments`. It lives on the
     * expense itself rather than a separate table: unlike a customer payment
     * (which can arrive in several instalments against one invoice), one
     * expense is one payment to one vendor, so there is nothing to split out.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('voucher_number')->nullable()->unique()->after('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn('voucher_number');
        });
    }
};
