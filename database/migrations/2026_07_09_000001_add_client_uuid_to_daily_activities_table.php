<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add client_uuid to daily_activities for offline sync deduplication.
     *
     * Mobile clients generate a UUID before submission. On reconnect, the server
     * checks this column so that replayed requests from the sync queue do not
     * create duplicate records.
     */
    public function up(): void
    {
        Schema::table('daily_activities', function (Blueprint $table) {
            $table->string('client_uuid', 36)->nullable()->unique()->after('gps_location')
                ->comment('Client-generated UUID for offline deduplication');
        });
    }

    public function down(): void
    {
        Schema::table('daily_activities', function (Blueprint $table) {
            $table->dropColumn('client_uuid');
        });
    }
};
