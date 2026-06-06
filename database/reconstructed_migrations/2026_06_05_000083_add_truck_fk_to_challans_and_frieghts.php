<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the legacy string `truck_no` references on `challans` and
 * `frieghts` with a proper `truck_id` FK to the new `trucks` table. The
 * legacy `truck_no` column is preserved (denormalized snapshot for print).
 *
 * The string `truck_id` columns added in migration 000010 are also upgraded
 * here to a real FK constraint (their column was unsignedBigInteger but
 * the constraint was intentionally deferred to a later migration).
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §10.2, §11 (recommends truck_id FK)
 * - docs/erd.md                             §2  (challan.truck_id, freight_memo.truck_id)
 * - docs/relationship-map.md                §3.4, §3.5
 * - docs/master-execution-roadmap.md        §3.4, §3.5
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the FK list.
 *             80% on the backfill — depends on whether every truck_no in
 *                   the legacy tables has a matching row in `trucks` after
 *                   the trucks table is populated.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Truck     (table: trucks — created in migration 000080)
 * - App\Models\challan   (table: challans — adds FK on truck_id)
 * - App\Models\Freight   (table: frieghts — adds FK on truck_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\ChallanController   (selects truck; uses with('truck') in eager-load)
 * - dash\FreightController   (selects truck; uses with('truck') in eager-load)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. The legacy `truck_no` (string) column is preserved on both tables.
 * 2. The `truck_id` column (added in migration 000010) is promoted to a
 *    real FK here.
 * 3. The FK uses `ON DELETE RESTRICT` — a truck that has been used on a
 *    challan / freight memo cannot be hard-deleted.
 * 4. If the legacy data has `truck_id` NULL on some rows, the FK still
 *    validates the constraint on the non-NULL rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('trucks')) {
            return;
        }

        $this->addTruckFk('challans');
        $this->addTruckFk('frieghts');
    }

    public function down(): void
    {
        $this->dropTruckFk('challans');
        $this->dropTruckFk('frieghts');
    }

    private function addTruckFk(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'truck_id')) {
            return;
        }
        $constraint = "fk_{$table}_truck_id";
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
            . "FOREIGN KEY (`truck_id`) REFERENCES `trucks` (`id`) "
            . "ON DELETE RESTRICT ON UPDATE CASCADE"
        );
    }

    private function dropTruckFk(string $table): void
    {
        $constraint = "fk_{$table}_truck_id";
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
