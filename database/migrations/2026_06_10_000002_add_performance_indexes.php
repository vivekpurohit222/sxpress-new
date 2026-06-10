<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add performance indexes to critical columns.
     * Per SXPRESS_PHASE_6 and LOGIC_SKILL section 10.
     *
     * Indexes:
     * - grs(office, copy_date) - for dashboard queries
     * - grs(office, status) - for filtered lists
     * - grs(gr_no) UNIQUE - for lookups
     * - frieghts(gr_no) - for linking
     * - gatepasses(gp_no) UNIQUE - for lookups
     * - challans(challan_no) UNIQUE - for lookups
     * - gatepass_gr(gr_id) - for reverse lookups
     *
     * @return void
     */
    public function up(): void
    {
        // GRs table indexes
        if (!Schema::hasIndex('grs', 'grs_office_copy_date_index')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->index(['office', 'copy_date'], 'grs_office_copy_date_index');
            });
        }

        if (!Schema::hasIndex('grs', 'grs_office_status_index')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->index(['office', 'status'], 'grs_office_status_index');
            });
        }

        if (!Schema::hasIndex('grs', 'grs_gr_no_unique')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->unique('gr_no', 'grs_gr_no_unique');
            });
        }

        // frieghts table indexes
        if (!Schema::hasIndex('frieghts', 'frieghts_gr_no_index')) {
            Schema::table('frieghts', function (Blueprint $table) {
                $table->index('gr_no', 'frieghts_gr_no_index');
            });
        }

        // gatepasses table indexes
        if (!Schema::hasIndex('gatepasses', 'gatepasses_gp_no_unique')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->unique('gp_no', 'gatepasses_gp_no_unique');
            });
        }

        if (!Schema::hasIndex('gatepasses', 'gatepasses_office_index')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->index('office', 'gatepasses_office_index');
            });
        }

        // challans table indexes
        if (!Schema::hasIndex('challans', 'challans_challan_no_unique')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->unique('challan_no', 'challans_challan_no_unique');
            });
        }

        if (!Schema::hasIndex('challans', 'challans_office_index')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->index('office', 'challans_office_index');
            });
        }

        // gatepass_gr pivot table index
        if (!Schema::hasIndex('gatepass_gr', 'gatepass_gr_gr_id_index')) {
            Schema::table('gatepass_gr', function (Blueprint $table) {
                $table->index('gr_id', 'gatepass_gr_gr_id_index');
            });
        }

        // challan_items table index
        if (!Schema::hasIndex('challan_items', 'challan_items_challan_id_index')) {
            Schema::table('challan_items', function (Blueprint $table) {
                $table->index('challan_id', 'challan_items_challan_id_index');
            });
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            $table->dropIndex('grs_office_copy_date_index');
            $table->dropIndex('grs_office_status_index');
            $table->dropUnique('grs_gr_no_unique');
        });

        Schema::table('frieghts', function (Blueprint $table) {
            $table->dropIndex('frieghts_gr_no_index');
        });

        Schema::table('gatepasses', function (Blueprint $table) {
            $table->dropUnique('gatepasses_gp_no_unique');
            $table->dropIndex('gatepasses_office_index');
        });

        Schema::table('challans', function (Blueprint $table) {
            $table->dropUnique('challans_challan_no_unique');
            $table->dropIndex('challans_office_index');
        });

        Schema::table('gatepass_gr', function (Blueprint $table) {
            $table->dropIndex('gatepass_gr_gr_id_index');
        });

        Schema::table('challan_items', function (Blueprint $table) {
            $table->dropIndex('challan_items_challan_id_index');
        });
    }
};