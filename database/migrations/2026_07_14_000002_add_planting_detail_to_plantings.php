<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How a cycle was actually planted — the basis for a yield projection.
        // French beans are drilled bed-by-bed, so we capture beds + seeds, with
        // area as a fallback basis when beds aren't used.
        Schema::table('plantings', function (Blueprint $table) {
            $table->integer('bed_count')->nullable()->after('quantity');
            $table->integer('seeds_sown')->nullable()->after('bed_count');
            $table->decimal('area_acres', 8, 2)->nullable()->after('seeds_sown');
        });
    }

    public function down(): void
    {
        Schema::table('plantings', function (Blueprint $table) {
            $table->dropColumn(['bed_count', 'seeds_sown', 'area_acres']);
        });
    }
};
