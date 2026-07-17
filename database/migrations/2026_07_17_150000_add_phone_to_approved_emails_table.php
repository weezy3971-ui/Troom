<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('approved_emails', 'phone')) {
            return;
        }

        Schema::table('approved_emails', function (Blueprint $table) {
            // Phone (stored 2547XXXXXXXX) the admin records when approving an
            // email. At registration the person must supply a matching number,
            // and the SMS OTP that confirms their identity is sent here.
            $table->string('phone')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('approved_emails', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
