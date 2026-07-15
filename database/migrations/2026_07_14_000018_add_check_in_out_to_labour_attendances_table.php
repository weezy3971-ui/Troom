<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional check-in / check-out for hourly (time-based) attendance. When
        // both are supplied, hours_worked is derived from them so there's a single
        // place to log worker time — no separate presence register.
        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('rate');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });
    }

    public function down(): void
    {
        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_out_at']);
        });
    }
};
