<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Projects are one-off work only (construction, land refining, training).
        // Recurring field operations (planting, weeding, spraying on a 6-month
        // cycle) belong to the crop-cycle flow, not here. project_type records
        // which kind of one-off this is.
        Schema::table('projects', function (Blueprint $table) {
            $table->string('project_type')->default('other')->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('project_type');
        });
    }
};
