<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A round of land preparation on a block. It starts before any crop
        // cycle exists — that is the whole problem it solves — and is linked to
        // the cycle later, so its spend lands against that planting instead of
        // floating as an unattached expense.
        Schema::create('land_preparations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('block_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_cycle_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('planned');
            $table->date('started_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['block_id', 'status']);
        });

        Schema::create('land_preparation_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('land_preparation_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sequence')->default(0);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 16)->default('pending');
            $table->date('done_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['land_preparation_id', 'sequence']);
        });

        // Lets a land-prep cost be filed against the preparation round it paid
        // for, rather than only against a farm or block.
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('land_preparation_id')->nullable()->after('block_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['land_preparation_id']);
            $table->dropColumn('land_preparation_id');
        });

        Schema::dropIfExists('land_preparation_tasks');
        Schema::dropIfExists('land_preparations');
    }
};
