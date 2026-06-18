<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gst_entries', function (Blueprint $table) {
            $table->id();
            $table->morphs('taxable');
            $table->date('transaction_date');
            $table->string('party_name');
            $table->string('party_gst_number', 20)->nullable();
            $table->decimal('gst_rate', 5, 2);
            $table->decimal('taxable_value', 12, 2);
            $table->decimal('cgst_amount', 12, 2)->default(0);
            $table->decimal('sgst_amount', 12, 2)->default(0);
            $table->decimal('igst_amount', 12, 2)->default(0);
            $table->decimal('total_tax', 12, 2);
            $table->enum('tax_direction', ['output', 'input']);
            $table->enum('gst_type', ['cgst_sgst', 'igst']);
            $table->string('branch');
            $table->string('hsn_sac_code', 10)->nullable()->default('996511');
            $table->timestamps();

            $table->index(['transaction_date', 'tax_direction']);
            $table->index(['branch', 'transaction_date']);
            $table->index('tax_direction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gst_entries');
    }
};
