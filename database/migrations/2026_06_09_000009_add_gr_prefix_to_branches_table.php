<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGrPrefixToBranchesTable extends Migration
{
    /**
     * Add gr_prefix column to branches table.
     * Per SXPRESS_MASTER_DOCUMENT section 4.7 and SXPRESS_LOGIC_SKILL section 13.
     * Used for GR number generation per branch.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('branches', 'gr_prefix')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->string('gr_prefix', 5)->unique()->nullable()->after('branch_code');
            });
        }
    }

    /**
     * @return void
     */
    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'gr_prefix')) {
                $table->dropColumn('gr_prefix');
            }
        });
    }
}