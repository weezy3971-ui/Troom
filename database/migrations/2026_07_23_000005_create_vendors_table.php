<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nothing in the schema represented a payee: expenses recorded a category
     * and an amount but never who was paid, and procurement requests carried
     * no supplier. B2C disbursement needs a payee with a payable MSISDN, so
     * this is the table it pays.
     *
     * Workers are deliberately not vendors — they already carry their own
     * `pay_phone` and are paid through labour attendance, not procurement.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->default('supplier'); // see Vendor::TYPES
            $table->string('phone')->nullable(); // 2547XXXXXXXX — the B2C destination
            $table->string('email')->nullable();
            $table->string('kra_pin')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('vendor_id')->nullable()->after('category')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('vendor_id');
        });

        Schema::dropIfExists('vendors');
    }
};
