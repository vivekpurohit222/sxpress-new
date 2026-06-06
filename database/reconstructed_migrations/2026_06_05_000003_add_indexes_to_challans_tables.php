<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes to `challans` and `challan_iteams`.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/performance-report.md §3.1
 * - docs/erd.md §4.2
 * - docs/master-execution-roadmap.md §4.12–4.15
 * - docs/database-reconstruction-report.md §7.4, §8.4
 *
 * --------------------------------------------------------------------------
 * Confidence: 100%
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\challan       (table: challans, PK = challan_no — string)
 * - App\Models\ChallanItem   (table: challan_iteams — preserved misspelling)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\ChallanController (AJAX getData / challanfetchdata; filters by challan_no, truck_no, challan_date)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * Pure ADD INDEX. No structural change. challans.challan_no remains the
 * current PK (string). The proper-PK + FK migration is handled separately.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->index('challan_date', 'idx_challans_challan_date');
            $table->index('truck_no',     'idx_challans_truck_no');
            $table->index(['from_dest', 'to_dest'], 'idx_challans_from_to');
        });

        Schema::table('challan_iteams', function (Blueprint $table) {
            $table->index('challan_no', 'idx_challan_iteams_challan_no');
            $table->index('gr_no',      'idx_challan_iteams_gr_no');
        });
    }

    public function down(): void
    {
        Schema::table('challans', function (Blueprint $table) {
            $table->dropIndex('idx_challans_challan_date');
            $table->dropIndex('idx_challans_truck_no');
            $table->dropIndex('idx_challans_from_to');
        });

        Schema::table('challan_iteams', function (Blueprint $table) {
            $table->dropIndex('idx_challan_iteams_challan_no');
            $table->dropIndex('idx_challan_iteams_gr_no');
        });
    }
};
