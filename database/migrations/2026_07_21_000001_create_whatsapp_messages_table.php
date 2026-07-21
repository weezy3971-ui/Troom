<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('test');
            $table->string('external_id')->unique();
            $table->string('channel_name')->nullable();
            $table->string('sender_phone', 32);
            $table->string('sender_name')->nullable();
            $table->text('body');
            $table->string('language', 16)->default('mixed');
            $table->string('intent', 32)->default('other');
            $table->json('extracted_data')->nullable();
            $table->decimal('confidence', 5, 4)->default(0);
            $table->string('status', 32)->default('pending_approval');
            $table->text('review_note')->nullable();
            $table->timestamp('received_at');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'received_at']);
            $table->index('sender_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
