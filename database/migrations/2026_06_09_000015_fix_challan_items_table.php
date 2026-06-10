<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixChallanItemsTable extends Migration
{
    /**
     * Fix challan_items table to use proper FK and add missing fields.
     * Per SXPRESS_LOGIC_SKILL section 6.
     *
     * @return void
     */
    public function up()
    {
        // Rename table if it still uses the old spelling
        if (Schema::hasTable('challan_iteams') && !Schema::hasTable('challan_items')) {
            Schema::rename('challan_iteams', 'challan_items');
        }

        if (!Schema::hasTable('challan_items')) {
            return; // table doesn't exist yet
        }

        // Check if table has the old structure and needs migration
        $hasChallanNo = Schema::hasColumn('challan_items', 'challan_no');
        $hasChallanId = Schema::hasColumn('challan_items', 'challan_id');

        if ($hasChallanNo && !$hasChallanId) {
            Schema::table('challan_items', function (Blueprint $table) {
                // Add challan_id FK
                $table->unsignedBigInteger('challan_id')->nullable()->after('id');

                // Add foreign key (after challans table is confirmed to exist)
                // Note: This is done separately below

                // Add missing fields
                if (!Schema::hasColumn('challan_items', 'remarks')) {
                    $table->text('remarks')->nullable()->after('weight');
                }
            });

            // Try to add FK if challans table exists
            if (Schema::hasTable('challans')) {
                Schema::table('challan_items', function (Blueprint $table) {
                    $table->foreign('challan_id')
                          ->references('id')
                          ->on('challans')
                          ->cascadeOnDelete();
                });
            }
        }

        // If gr_no has unique constraint, remove it (multiple items can reference same GR)
        // This depends on the DB driver - we'll handle it in a seeder or manually if needed
    }

    /**
     * @return void
     */
    public function down()
    {
        // No rollback needed for structural fix
    }
}