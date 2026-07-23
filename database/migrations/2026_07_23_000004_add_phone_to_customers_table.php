<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `contact` is free text (a name, a landline, an email — whatever was
     * typed). An STK Push needs a validated MSISDN, so payment phone numbers
     * get their own column, normalised through SmsService::normalizePhone().
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('contact'); // 2547XXXXXXXX
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
