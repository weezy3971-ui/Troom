<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * crop_cycles is the spec's planting_cycles table — a block running a
     * template. The table keeps its name because nineteen other tables hold
     * crop_cycle_id foreign keys into it; renaming buys nothing and SQLite
     * renames are destructive. What it gains here is the template link and the
     * current-stage pointer the spec calls for.
     */
    public function up(): void
    {
        Schema::table('crop_cycles', function (Blueprint $table) {
            $table->foreignId('crop_cycle_template_id')->nullable()->after('crop_id')
                ->constrained()->nullOnDelete();
            $table->foreignId('current_stage_id')->nullable()->after('expected_harvest_date')
                ->constrained('crop_cycle_stages')->nullOnDelete();
            $table->date('actual_end_date')->nullable()->after('expected_harvest_date');
        });
    }

    public function down(): void
    {
        Schema::table('crop_cycles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('crop_cycle_template_id');
            $table->dropConstrainedForeignId('current_stage_id');
            $table->dropColumn('actual_end_date');
        });
    }
};
