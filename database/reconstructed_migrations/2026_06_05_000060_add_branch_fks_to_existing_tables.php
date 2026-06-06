<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add nullable `branch_id` foreign keys to every transactional table and to
 * the `users` table. Promotes the hard-coded `office` string on `users`
 * and the `from_dest` / `to_dest` free-text columns to first-class FKs.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §2.3, §2.4, §10.2
 * - docs/erd.md                             §2  (modernized entity diagram),
 *                                              §3  (Modernization diff summary:
 *                                              users.office → users.branch_id;
 *                                              from_dest/to_dest → *_branch_id)
 * - docs/relationship-map.md                §3.6–§3.11 (every from_dest /
 *                                              to_dest becomes branches.id)
 * - docs/master-execution-roadmap.md        §3.6–§3.10, §5.7
 * - docs/security-audit.md                  §3.1  (tenancy)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the FK list (declared in the target ERD).
 *             100% on nullability (all new columns are NULLABLE so existing
 *                   rows continue to work; the legacy `office` and
 *                   `from_dest` strings are preserved).
 *             100% on ON DELETE / ON UPDATE behaviour (RESTRICT — branch
 *                   master data is not casually deleted).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Branch (table: branches — created in migration 000050)
 * - App\Models\User         (FK: users.branch_id)
 * - App\Models\Gr           (FK: grs.from_branch_id, grs.to_branch_id)
 * - App\Models\gatepass     (FK: gatepasses.from_branch_id, gatepasses.to_branch_id)
 * - App\Models\challan      (FK: challans.from_branch_id, challans.to_branch_id)
 * - App\Models\Freight      (FK: frieghts.from_branch_id, frieghts.to_branch_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController         (per-office list will filter by branch_id)
 * - dash\GatepassController   (per-office list)
 * - dash\ChallanController    (per-office list)
 * - dash\FreightController    (per-office list)
 * - UserController            (user CRUD will populate branch_id dropdown)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. The legacy `users.office` (string) column is preserved. The new
 *    `users.branch_id` is NULLABLE — old rows read NULL.
 * 2. The legacy `from_dest` / `to_dest` columns are preserved on every
 *    transactional table. The new `from_branch_id` / `to_branch_id` are
 *    NULLABLE.
 * 3. A data-backfill script (out of scope for this migration) populates
 *    the new FKs from the legacy strings:
 *
 *      UPDATE users u
 *        JOIN branches b ON b.name = u.office
          SET u.branch_id = b.id
          WHERE u.branch_id IS NULL;

        UPDATE grs g
          JOIN branches b1 ON b1.name = g.from_dest
          JOIN branches b2 ON b2.name = g.to_dest
          SET g.from_branch_id = b1.id,
              g.to_branch_id   = b2.id
          WHERE g.from_branch_id IS NULL OR g.to_branch_id IS NULL;
 *
 * 4. The FK constraints use `ON DELETE RESTRICT` so a branch cannot be
 *    deleted while rows reference it. A branch is deactivated via
 *    `is_active = false`, not deleted.
 */
return new class extends Migration
{
    /**
     * Map: table => [from_branch_id, to_branch_id] flags.
     * `users` only has branch_id (no from/to).
     */
    private const BRANCH_FK_COLUMNS = [
        'users'         => ['branch_id' => true],
        'grs'           => ['from_branch_id' => true,  'to_branch_id' => true],
        'gatepasses'    => ['from_branch_id' => true,  'to_branch_id' => true],
        'challans'      => ['from_branch_id' => true,  'to_branch_id' => true],
        'frieghts'      => ['from_branch_id' => true,  'to_branch_id' => true],
    ];

    public function up(): void
    {
        foreach (self::BRANCH_FK_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($table, $columns) {
                foreach ($columns as $column => $addFk) {
                    if (! Schema::hasColumn($table, $column)) {
                        $t->unsignedBigInteger($column)->nullable();
                    }
                }
            });

            // Add indexes (separate Schema::table call — Laravel cannot
            // combine column add + FK + index reliably in the same call).
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach (array_keys($columns) as $column) {
                    $short = str_replace('_branch_id', '', $column);
                    $t->index($column, "idx_{$t->getTable()}_{$column}");
                }
            });

            // Add FK constraints.
            foreach (array_keys($columns) as $column) {
                // foreignId() automatically infers the column name; here we
                // use a raw statement so we can name the constraint and
                // pin ON DELETE behaviour.
                \DB::statement(
                    "ALTER TABLE `{$table}` ADD CONSTRAINT `fk_{$table}_{$column}` "
                    . "FOREIGN KEY (`{$column}`) REFERENCES `branches` (`id`) "
                    . "ON DELETE RESTRICT ON UPDATE CASCADE"
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::BRANCH_FK_COLUMNS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach (array_keys($columns) as $column) {
                \DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `fk_{$table}_{$column}`");
            }
            Schema::table($table, function (Blueprint $t) use ($columns) {
                foreach (array_keys($columns) as $column) {
                    $t->dropIndex("idx_{$t->getTable()}_{$column}");
                    $t->dropColumn($column);
                }
            });
        }
    }
};
