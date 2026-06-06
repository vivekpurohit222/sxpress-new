<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promote the `gr_id` and `challan_id` columns (added in migration 000010)
 * to real FK constraints pointing at `grs.id` and `challans.id`.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §10.2
 * - docs/erd.md                             §2  (gatepass.gr_id, challan_line.gr_id)
 * - docs/relationship-map.md                §3.1, §3.2, §3.3
 * - docs/master-execution-roadmap.md        §3.1, §3.2, §3.3
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the FK list.
 *             80% on the backfill — depends on whether the legacy gr_no /
 *                   challan_no strings still match the new ids.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr           (FK target)
 * - App\Models\gatepass     (FK: gr_id)
 * - App\Models\challan      (FK target; its `id` was added in migration 000010)
 * - App\Models\ChallanItem  (FK: gr_id, challan_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController        (read by gr_id)
 * - dash\GatepassController  (write gr_id; read by gr_id)
 * - dash\ChallanController   (write gr_id, challan_id)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. The columns were added as nullable in migration 000010. Existing rows
 *    have NULL — they continue to satisfy the constraint.
 * 2. The legacy gr_no (string) column is preserved.
 * 3. ON DELETE RESTRICT for gr_id (a GR is a financial document; cannot
 *    hard-delete); ON DELETE CASCADE for challan_iteams.challan_id
 *    (challan lines are owned by the challan).
 */
return new class extends Migration
{
    public function up(): void
    {
        // gatepasses.gr_id → grs.id
        $this->addFk('gatepasses', 'gr_id', 'grs', 'fk_gatepasses_gr_id', 'restrict');

        // challan_iteams.gr_id → grs.id
        $this->addFk('challan_iteams', 'gr_id', 'grs', 'fk_challan_iteams_gr_id', 'restrict');

        // challan_iteams.challan_id → challans.id
        $this->addFk('challan_iteams', 'challan_id', 'challans', 'fk_challan_iteams_challan_id', 'cascade');
    }

    public function down(): void
    {
        $this->dropFk('challan_iteams', 'fk_challan_iteams_challan_id');
        $this->dropFk('challan_iteams', 'fk_challan_iteams_gr_id');
        $this->dropFk('gatepasses', 'fk_gatepasses_gr_id');
    }

    private function addFk(string $table, string $column, string $refTable, string $constraint, string $onDelete): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return;
        }
        if (! Schema::hasTable($refTable)) {
            return;
        }
        $exists = \DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM information_schema.table_constraints
              WHERE table_schema    = DATABASE()
                AND table_name      = ?
                AND constraint_name = ?",
            [$table, $constraint]
        );
        if ($exists && $exists->c > 0) {
            return;
        }
        \DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` "
            . "FOREIGN KEY (`{$column}`) REFERENCES `{$refTable}` (`id`) "
            . "ON DELETE {$onDelete} ON UPDATE CASCADE"
        );
    }

    private function dropFk(string $table, string $constraint): void
    {
        $exists = \DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM information_schema.table_constraints
              WHERE table_schema    = DATABASE()
                AND table_name      = ?
                AND constraint_name = ?",
            [$table, $constraint]
        );
        if (! $exists || $exists->c === 0) {
            return;
        }
        \DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
    }
};
