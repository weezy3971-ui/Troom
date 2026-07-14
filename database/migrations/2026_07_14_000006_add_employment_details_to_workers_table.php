<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Casual vs permanent split (recording: casuals paid on target, permanent
        // staff time-based) plus ID / employee-number for the worker record.
        // `pay_phone` is reserved only — mobile-money pay-out is deferred to its
        // own plan and no disbursement logic exists here.
        Schema::table('workers', function (Blueprint $table) {
            $table->string('worker_type')->default('casual')->after('name');
            $table->string('national_id')->nullable()->after('worker_type');
            $table->string('employee_no')->nullable()->after('national_id');
            $table->string('pay_phone')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('workers', function (Blueprint $table) {
            $table->dropColumn(['worker_type', 'national_id', 'employee_no', 'pay_phone']);
        });
    }
};
