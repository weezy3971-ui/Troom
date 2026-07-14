<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Inputs the yield/revenue projection engine needs. Agronomists reason in
        // beds (seeds/bed, kg/bed); revenue needs a reference price; germination is
        // the default assumption used before a real field reading exists.
        Schema::table('crops', function (Blueprint $table) {
            $table->integer('seeds_per_bed')->nullable()->after('expected_yield_per_acre');
            $table->decimal('expected_yield_per_bed_kg', 10, 2)->nullable()->after('seeds_per_bed');
            $table->decimal('reference_price_per_kg', 10, 2)->nullable()->after('expected_yield_per_bed_kg');
            // Stored as a fraction (0.90 = 90%).
            $table->decimal('expected_germination_rate', 4, 3)->nullable()->after('reference_price_per_kg');
        });
    }

    public function down(): void
    {
        Schema::table('crops', function (Blueprint $table) {
            $table->dropColumn([
                'seeds_per_bed',
                'expected_yield_per_bed_kg',
                'reference_price_per_kg',
                'expected_germination_rate',
            ]);
        });
    }
};
