<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Rename legacy typo / opaque columns to canonical names.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/ghost-field-audit.md               §2.1–§2.4, §2.8, §2.9
 *   (nor_adress, nee_adress, nor_gst_no, nee_gst_no, m_s, balance_to_sn,
 *    dc_amount)
 * - docs/database-reconstruction-report.md  §13  (typo column list)
 * - docs/erd.md                             §3  (Modernization diff summary)
 * - docs/master-execution-roadmap.md        §5.15 (Migration 5.15)
 * - docs/refactoring-plan.md                §3   (column renames)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the rename list (every rename is documented in
 *             ghost-field-audit.md). 100% on the SQL safety (RENAME COLUMN
 *             is non-destructive in MySQL 8 — column data is preserved).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr             (grs.nor_adress, grs.nee_adress,
 *                              grs.nor_gst_no, grs.nee_gst_no)
 * - App\Models\gatepass       (gatepasses.m_s, gatepasses.dc_amount)
 * - App\Models\Freight        (frieghts.balance_to_sn)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController         (reads/writes nor_adress, nee_adress,
 *                              nor_gst_no, nee_gst_no)
 * - dash\GatepassController   (reads/writes m_s, dc_amount)
 * - dash\FreightController    (reads/writes balance_to_sn)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. RENAME COLUMN is non-destructive in MySQL 8.0+. The data is preserved
 *    and FK/index references are updated automatically.
 * 2. Application code that still references the old names will FAIL —
 *    this migration is intentionally NOT backward-compatible at the code
 *    level (per docs/master-execution-roadmap.md §3.4, model/view updates
 *    are bundled with this migration in the modernization phase).
 * 3. The down() restores the legacy names so the migration can be rolled
 *    back during a failed deployment.
 * 4. Indexes on the renamed columns are also renamed automatically by
 *    MySQL; we do not re-create them here.
 */
return new class extends Migration
{
    /**
     * Forward renames. Source => destination.
     *
     * Each entry is: [table, old_column, new_column]
     */
    private const RENAMES = [
        // grs — typo fixes
        ['grs', 'nor_adress',  'consignor_address'],
        ['grs', 'nee_adress',  'consignee_address'],
        ['grs', 'nor_gst_no',  'consignor_gst_no'],
        ['grs', 'nee_gst_no',  'consignee_gst_no'],

        // gatepasses — opaque names
        ['gatepasses', 'm_s',        'consignor'],
        ['gatepasses', 'dc_amount',  'delivery_charge'],

        // frieghts — opaque internal shorthand
        ['frieghts', 'balance_to_sn', 'balance_due'],
    ];

    public function up(): void
    {
        foreach (self::RENAMES as [$table, $old, $new]) {
            if (! Schema::hasColumn($table, $old)) {
                // Old column already gone — likely a previous run. Skip.
                continue;
            }
            if (Schema::hasColumn($table, $new)) {
                // New column already exists — assume the rename was
                // performed manually. Skip to avoid data loss.
                continue;
            }
            // MySQL 8.0+ RENAME COLUMN
            DB::statement("ALTER TABLE `{$table}` RENAME COLUMN `{$old}` TO `{$new}`");
        }
    }

    public function down(): void
    {
        // Reverse the order — most recent renames first.
        $reverse = array_reverse(self::RENAMES);
        foreach ($reverse as [$table, $old, $new]) {
            if (! Schema::hasColumn($table, $new)) {
                continue;
            }
            if (Schema::hasColumn($table, $old)) {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` RENAME COLUMN `{$new}` TO `{$old}`");
        }
    }
};
