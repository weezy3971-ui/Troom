<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The crop_programs / crop_program_stages pair was an earlier attempt at a
     * reusable planting protocol, and crop_cycle_stages materialised it onto a
     * cycle. None of it was wired into cycle operations. The spec replaces all
     * three with crop_cycle_templates + crop_cycle_stages + schedule points,
     * created in the migrations that follow this one.
     */
    public function up(): void
    {
        Schema::dropIfExists('crop_cycle_stages');
        Schema::dropIfExists('crop_program_stages');
        Schema::dropIfExists('crop_programs');
    }

    public function down(): void
    {
        // Legacy tables are not restored: the spec structures supersede them.
    }
};
