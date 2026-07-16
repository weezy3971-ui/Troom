<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('outgrowers', function (Blueprint $table) {
            $table->string('specialization')->nullable()->after('notes');
            $table->tinyInteger('reliability_rating')->nullable()->after('specialization');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('outgrowers', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['specialization', 'reliability_rating']);
        });
    }
};
