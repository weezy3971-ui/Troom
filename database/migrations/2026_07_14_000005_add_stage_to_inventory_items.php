<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Separates pre-harvest inputs (fertiliser, chemicals) from post-harvest
        // packaging (crates, cartons) so each side of the store can be managed and
        // reported on its own. Existing rows default to 'general'.
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->string('stage')->default('general')->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('stage');
        });
    }
};
