<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance indexes to the existing `grs` table.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/performance-report.md §3.1 (Missing indexes — application tables)
 * - docs/erd.md §4.1 (Critical for scale)
 * - docs/master-execution-roadmap.md §4.1–4.7 (P0/P1 index list)
 * - docs/database-reconstruction-report.md §4.5
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% (every index corresponds to an observed query path)
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr (table: grs)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController       (filters by from_dest, copy_date; searches consignor/consignee/eway_bill_number)
 * - dash\GatepassController (looks up by gr_no)
 * - dash\ChallanController  (AJAX getData($gr_no))
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * Pure ADD INDEX operations. No column is dropped, renamed, or modified.
 * Existing application code is unaffected. Safe to run on a populated DB.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            // Single-column indexes
            $table->index('from_dest',  'idx_grs_from_dest');
            $table->index('to_dest',    'idx_grs_to_dest');
            $table->index('copy_date',  'idx_grs_copy_date');
            $table->index('consignor',  'idx_grs_consignor');
            $table->index('consignee',  'idx_grs_consignee');
            $table->index('eway_bill_number', 'idx_grs_eway_bill_number');

            // Composite index — most common query: list per booking office, date-sorted
            // (see performance-report.md §3.2: ~600x speedup on a 1M-row table)
            $table->index(['from_dest', 'copy_date'], 'idx_grs_from_dest_copy_date');
        });
    }

    public function down(): void
    {
        Schema::table('grs', function (Blueprint $table) {
            $table->dropIndex('idx_grs_from_dest');
            $table->dropIndex('idx_grs_to_dest');
            $table->dropIndex('idx_grs_copy_date');
            $table->dropIndex('idx_grs_consignor');
            $table->dropIndex('idx_grs_consignee');
            $table->dropIndex('idx_grs_eway_bill_number');
            $table->dropIndex('idx_grs_from_dest_copy_date');
        });
    }
};
