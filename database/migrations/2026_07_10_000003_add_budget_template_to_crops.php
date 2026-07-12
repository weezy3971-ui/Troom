<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Reusable per-crop budget template: default figures pre-fill a new
        // crop cycle's seasonal budget so growers don't re-key them each season.
        Schema::table('crops', function (Blueprint $table) {
            $table->decimal('default_labour_budget', 12, 2)->nullable()->after('expected_yield_per_acre');
            $table->decimal('default_input_budget', 12, 2)->nullable()->after('default_labour_budget');
            $table->decimal('default_irrigation_budget', 12, 2)->nullable()->after('default_input_budget');
            $table->decimal('default_overhead_budget', 12, 2)->nullable()->after('default_irrigation_budget');
        });
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn([
                'default_labour_budget',
                'default_input_budget',
                'default_irrigation_budget',
                'default_overhead_budget',
            ]);
        });
    }
};
