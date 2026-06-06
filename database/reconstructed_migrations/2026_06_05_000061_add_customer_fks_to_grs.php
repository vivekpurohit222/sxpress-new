<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add nullable `consignor_id` and `consignee_id` foreign keys to `grs`.
 * Each points to the new `customers` table.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — customers)
 * - docs/erd.md                             §2  (CUSTOMER entity),
 *                                              §3  (grs.consignor_id,
 *                                              grs.consignee_id FKs)
 * - docs/relationship-map.md                §3.12 (FKs from grs to customers)
 * - docs/business-workflows.md              §9  (customer GST reconciliation)
 * - docs/master-execution-roadmap.md        §3.12, §5.8
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the FK list and column types.
 *             100% on nullability — existing rows have NULL until the
 *                   backfill runs.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Customer (table: customers — created in migration 000053)
 * - App\Models\Gr       (table: grs — gets consignor_id, consignee_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController         (form's GST lookup selects a customer)
 * - (NEW) dash\CustomerController  (CRUD)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. The free-text `grs.consignor` / `grs.consignee` / `grs.consignor_gst_no`
 *    / `grs.consignee_gst_no` columns are preserved (denormalized snapshot).
 * 2. New FKs are NULLABLE — existing rows are not touched.
 * 3. A backfill script (out of scope) populates the FKs:
 *
 *      INSERT INTO customers (code, name, gst_no, is_active, created_at, updated_at)
 *      SELECT DISTINCT
 *          CONCAT('C', LPAD(@row := @row + 1, 4, '0')) AS code,
 *          consignor, consignor_gst_no, 1, NOW(), NOW()
 *      FROM grs, (SELECT @row := 0) r
 *      WHERE consignor IS NOT NULL AND consignor <> '';
 *
 *      UPDATE grs g
 *        JOIN customers c ON c.name = g.consignor
 *                       AND c.gst_no <=> g.consignor_gst_no
 *        SET g.consignor_id = c.id
 *        WHERE g.consignor_id IS NULL;
 *
 * 4. ON DELETE RESTRICT — a customer with GR history cannot be hard-deleted.
 *    Soft-delete is the policy.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers')) {
            // Migration 000053 must run first.
            return;
        }
        if (! Schema::hasTable('grs')) {
            return;
        }

        Schema::table('grs', function (Blueprint $table) {
            if (! Schema::hasColumn('grs', 'consignor_id')) {
                $table->unsignedBigInteger('consignor_id')->nullable()->after('consignee');
            }
            if (! Schema::hasColumn('grs', 'consignee_id')) {
                $table->unsignedBigInteger('consignee_id')->nullable()->after('consignee_id' === '' ? 'consignor_id' : 'consignee_id');
            }
        });

        Schema::table('grs', function (Blueprint $table) {
            $table->index('consignor_id', 'idx_grs_consignor_id');
            $table->index('consignee_id', 'idx_grs_consignee_id');
        });

        \DB::statement(
            'ALTER TABLE `grs` ADD CONSTRAINT `fk_grs_consignor_id` '
            . 'FOREIGN KEY (`consignor_id`) REFERENCES `customers` (`id`) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );
        \DB::statement(
            'ALTER TABLE `grs` ADD CONSTRAINT `fk_grs_consignee_id` '
            . 'FOREIGN KEY (`consignee_id`) REFERENCES `customers` (`id`) '
            . 'ON DELETE RESTRICT ON UPDATE CASCADE'
        );
    }

    public function down(): void
    {
        if (! Schema::hasTable('grs')) {
            return;
        }

        \DB::statement('ALTER TABLE `grs` DROP FOREIGN KEY `fk_grs_consignee_id`');
        \DB::statement('ALTER TABLE `grs` DROP FOREIGN KEY `fk_grs_consignor_id`');

        Schema::table('grs', function (Blueprint $table) {
            $table->dropIndex('idx_grs_consignee_id');
            $table->dropIndex('idx_grs_consignor_id');
            $table->dropColumn(['consignor_id', 'consignee_id']);
        });
    }
};
