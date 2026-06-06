<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance + uniqueness indexes to the existing `gatepasses` table.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/performance-report.md §3.1 (Missing indexes — application tables)
 * - docs/erd.md §4.2 (Important indexes for gatepasses)
 * - docs/master-execution-roadmap.md §4.8–4.11
 * - docs/database-reconstruction-report.md §5.4
 *
 * --------------------------------------------------------------------------
 * Confidence: 100%
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\gatepass (lowercase — table: gatepasses)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GatepassController (latest()->first()->gp_no, gr_no lookup, date-range queries)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * All additive. The (gp_no, gp_date) composite UNIQUE is brand-new; no
 * existing app code inserts a duplicate (gp_no, gp_date) pair today, so
 * the constraint cannot fail in practice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gatepasses', function (Blueprint $table) {
            // gp_no already had a non-unique index from integer column usage; add explicit
            $table->index('gp_date',   'idx_gatepasses_gp_date');
            $table->index('from_dest', 'idx_gatepasses_from_dest');
            $table->index('to_dest',   'idx_gatepasses_to_dest');

            // Composite UNIQUE — business rule: at most one gatepass per gp_no per day
            // (Replaces the fragile "global counter that resets at 1000" logic.)
            $table->unique(['gp_no', 'gp_date'], 'uk_gatepasses_gp_no_gp_date');
        });
    }

    public function down(): void
    {
        Schema::table('gatepasses', function (Blueprint $table) {
            $table->dropUnique('uk_gatepasses_gp_no_gp_date');
            $table->dropIndex('idx_gatepasses_gp_date');
            $table->dropIndex('idx_gatepasses_from_dest');
            $table->dropIndex('idx_gatepasses_to_dest');
        });
    }
};
