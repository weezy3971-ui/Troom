<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Allowlist: only emails present here (and not yet registered) may
        // self-register. The assigned role is applied to the new account.
        Schema::create('approved_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('role')->default('farm_supervisor');
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });

        // Deactivated users keep their record but cannot sign in.
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
        Schema::dropIfExists('approved_emails');
    }
};
