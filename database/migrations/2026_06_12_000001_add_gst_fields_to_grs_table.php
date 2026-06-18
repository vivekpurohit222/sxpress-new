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
        Schema::table('grs', function (Blueprint $table) {
            $table->decimal('gst_rate', 5, 2)->nullable()->after('total_amount');
            $table->enum('gst_type', ['cgst_sgst', 'igst'])->nullable()->after('gst_rate');
            $table->decimal('cgst_amount', 12, 2)->nullable()->default(0)->after('gst_type');
            $table->decimal('sgst_amount', 12, 2)->nullable()->default(0)->after('cgst_amount');
            $table->decimal('igst_amount', 12, 2)->nullable()->default(0)->after('sgst_amount');
            $table->decimal('gst_total', 12, 2)->nullable()->default(0)->after('igst_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            $table->dropColumn([
                'gst_rate',
                'gst_type',
                'cgst_amount',
                'sgst_amount',
                'igst_amount',
                'gst_total',
            ]);
        });
    }
};
