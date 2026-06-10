<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Update frieghts table to have the correct schema for Freight model.
     * The original table (2020_11_08) had a different structure.
     * New structure matches Freight model fillable:
     * memo_no, memo_date, gr_no, consignor, consignee,
     * freight_amount, other_charges, total, payment_type, payment_status, remarks
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('frieghts', function (Blueprint $table) {
            // Rename old columns if they exist, or add new ones
            if (!Schema::hasColumn('frieghts', 'memo_no')) {
                $table->string('memo_no', 20)->nullable()->after('id');
            }

            if (!Schema::hasColumn('frieghts', 'memo_date')) {
                $table->string('memo_date', 20)->nullable()->after('memo_no');
            }

            if (!Schema::hasColumn('frieghts', 'gr_no')) {
                $table->string('gr_no', 20)->nullable()->after('memo_date');
            }

            if (!Schema::hasColumn('frieghts', 'consignor')) {
                $table->string('consignor', 200)->nullable()->after('gr_no');
            }

            if (!Schema::hasColumn('frieghts', 'consignee')) {
                $table->string('consignee', 200)->nullable()->after('consignor');
            }

            if (!Schema::hasColumn('frieghts', 'freight_amount')) {
                $table->decimal('freight_amount', 10, 2)->nullable()->after('consignee');
            }

            if (!Schema::hasColumn('frieghts', 'total')) {
                $table->decimal('total', 10, 2)->nullable()->after('freight_amount');
            }

            if (!Schema::hasColumn('frieghts', 'payment_type')) {
                $table->enum('payment_type', ['paid', 'to_pay'])->nullable()->after('total');
            }

            if (!Schema::hasColumn('frieghts', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'collected'])->default('pending')->after('payment_type');
            }

            if (!Schema::hasColumn('frieghts', 'remarks')) {
                $table->text('remarks')->nullable()->after('payment_status');
            }
        });
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('frieghts', function (Blueprint $table) {
            $columns = ['memo_no', 'memo_date', 'gr_no', 'consignor', 'consignee',
                        'freight_amount', 'total', 'payment_type', 'payment_status', 'remarks'];

            foreach ($columns as $column) {
                if (Schema::hasColumn('frieghts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};