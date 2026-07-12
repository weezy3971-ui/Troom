<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Audit trail: who did what, to which record, from where, and when.
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');                       // created, updated, deleted, signed_in, ...
            $table->nullableMorphs('subject');              // subject_type + subject_id
            $table->string('description');                  // human-readable summary
            $table->json('properties')->nullable();         // changed attributes, etc.
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['action']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
