<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->foreignId('block_id')->nullable()->after('extracted_data')->constrained()->nullOnDelete();
            $table->foreignId('crop_cycle_id')->nullable()->after('block_id')->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3)->nullable()->after('crop_cycle_id');
            $table->string('unit', 24)->nullable()->after('quantity');
            $table->date('event_date')->nullable()->after('unit');
            $table->nullableMorphs('posted_record');
            $table->timestamp('posted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('block_id');
            $table->dropConstrainedForeignId('crop_cycle_id');
            $table->dropMorphs('posted_record');
            $table->dropColumn(['quantity', 'unit', 'event_date', 'posted_at']);
        });
    }
};
