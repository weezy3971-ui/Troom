<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Casual labour is paid by target/piece-rate ("5 beds = 600 KES"), not
        // hourly, so an entry is either hourly (hours × rate) or target
        // (qty_completed × rate_per_unit). The hourly columns already exist; the
        // unused side of each entry is stored as 0 rather than altered to null
        // (SQLite column changes rebuild the table and can drop foreign keys).
        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->foreignId('worker_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('pay_basis')->default('hourly')->after('task');
            $table->string('target_unit')->nullable()->after('pay_basis');
            $table->decimal('target_qty', 10, 2)->nullable()->after('target_unit');
            $table->decimal('qty_completed', 10, 2)->nullable()->after('target_qty');
            $table->decimal('rate_per_unit', 10, 2)->nullable()->after('qty_completed');
        });
    }

    public function down(): void
    {
        Schema::table('labour_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('worker_id');
            $table->dropColumn(['pay_basis', 'target_unit', 'target_qty', 'qty_completed', 'rate_per_unit']);
            // hours_worked / rate were never altered, so nothing to restore here.
        });
    }
};
