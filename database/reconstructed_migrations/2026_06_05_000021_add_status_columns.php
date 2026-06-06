<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Add lifecycle status columns to transactional tables.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §4.2, §5.3, §7.3, §8.3
 *                                              (status is implicit today)
 * - docs/business-workflows.md              §2 (GR → Gatepass → Challan →
 *                                              POD flow) and §8 (POD lifecycle)
 * - docs/erd.md                             §2  (challan, gatepass modern
 *                                              schemas do not yet have a
 *                                              status — but erp-workflow-map
 *                                              §2/§3/§5 imply one)
 * - docs/erp-workflow-map.md                §2, §3, §5, §8
 *                                              (delivery flow: GR booked →
 *                                              in_transit → delivered → closed)
 * - docs/master-execution-roadmap.md        §5.21 (Migration 5.21)
 * - docs/refactoring-plan.md                §3.1  (number sequences; status
 *                                              touches the same state machine)
 *
 * --------------------------------------------------------------------------
 * Confidence: 90% (status values are inferred from workflow docs; the user
 *             may want to refine the enum. Default values are safe — every
 *             new column defaults to 'booked' so existing rows behave as
 *             before.)
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr             (table: grs)
 * - App\Models\gatepass       (table: gatepasses)
 * - App\Models\challan        (table: challans)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController          (creates grs in 'booked' state)
 * - dash\GatepassController    (transitions GR to 'in_transit' implicitly)
 * - dash\ChallanController     (transitions GR to 'in_transit' / delivered)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. Every new column is NULLABLE with default 'booked' — existing rows
 *    read the default; nothing breaks.
 * 2. The enum values are stored as VARCHAR; the application layer enforces
 *    the legal set via the model's $casts (a follow-up change; the
 *    migration itself does not change the model's $fillable).
 * 3. No existing column is dropped, renamed, or modified.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ---- grs: add status -------------------------------------------------
        if (! Schema::hasColumn('grs', 'status')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->default('booked')->after('total_amount');
                $table->index('status', 'idx_grs_status');
            });
        }

        // ---- gatepasses: add status ------------------------------------------
        if (! Schema::hasColumn('gatepasses', 'status')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->default('booked')->after('note');
                $table->index('status', 'idx_gatepasses_status');
            });
        }

        // ---- challans: add status --------------------------------------------
        if (! Schema::hasColumn('challans', 'status')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->string('status', 20)->nullable()->default('booked')->after('challan_total');
                $table->index('status', 'idx_challans_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('grs', 'status')) {
            Schema::table('grs', function (Blueprint $table) {
                $table->dropIndex('idx_grs_status');
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('gatepasses', 'status')) {
            Schema::table('gatepasses', function (Blueprint $table) {
                $table->dropIndex('idx_gatepasses_status');
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('challans', 'status')) {
            Schema::table('challans', function (Blueprint $table) {
                $table->dropIndex('idx_challans_status');
                $table->dropColumn('status');
            });
        }
    }
};
