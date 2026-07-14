<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A second person verifies the weighed quantity ("confirmed by who actually
        // confirmed") before the batch is trusted for packing and sales.
        Schema::table('harvest_batches', function (Blueprint $table) {
            $table->foreignId('confirmed_by')->nullable()->after('harvested_by')->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable()->after('confirmed_by');
        });
    }

    public function down(): void
    {
        Schema::table('harvest_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('confirmed_by');
            $table->dropColumn('confirmed_at');
        });
    }
};
