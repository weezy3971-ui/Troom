<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Capture whether the labourer is casual or in-house, plus their phone and
        // ID, on the attendance itself. Roster workers auto-fill these; ad-hoc
        // casuals can be typed in directly so identity is still recorded.
        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->string('worker_type')->nullable()->after('worker_name');
            $table->string('worker_phone')->nullable()->after('worker_type');
            $table->string('worker_national_id')->nullable()->after('worker_phone');
        });
    }

    public function down(): void
    {
        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->dropColumn(['worker_type', 'worker_phone', 'worker_national_id']);
        });
    }
};
