<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add soft-delete + audit columns to every business table.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/erd.md                             §5 (Soft-delete strategy table),
 *                                            §6 (Audit columns)
 * - docs/database-reconstruction-report.md  §11 (Missing tables — audit_logs;
 *                                              this migration is the
 *                                              consumer-side half)
 * - docs/security-audit.md                  §12.2 (audit, regulatory retention)
 * - docs/master-execution-roadmap.md        §5.17 (Migration 5.17)
 * - docs/refactoring-plan.md                §3.7  (audit observers)
 * - docs/business-workflows.md              §1–§8 (every business entity is
 *                                              auditable; every one should
 *                                              be soft-deletable for
 *                                              compliance)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the column list (the ERD specifies exactly which
 *             tables get soft delete vs. not). 100% on the column types
 *             and nullability.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr             (gets deleted_at, created_by_id, updated_by_id)
 * - App\Models\gatepass       (gets deleted_at, created_by_id, updated_by_id)
 * - App\Models\challan        (gets deleted_at, created_by_id, updated_by_id)
 * - App\Models\ChallanItem    (no soft-delete; gets created_by_id, updated_by_id)
 * - App\Models\Freight        (gets deleted_at, created_by_id, updated_by_id)
 * - App\Models\truckdriver    (gets deleted_at, created_by_id, updated_by_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController          (use SoftDeletes trait on Gr; observe create/update)
 * - dash\GatepassController    (same)
 * - dash\ChallanController     (same)
 * - dash\FreightController     (same)
 * - TruckdriverController      (same)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. Every new column is NULLABLE. Existing rows have NULL — no behavior
 *    change. Application code does not break.
 * 2. The `SoftDeletes` trait is NOT added to the Eloquent models by this
 *    migration. Models must opt in (a separate code change). Until then,
 *    `->delete()` still does a hard delete; the `deleted_at` column is
 *    just dead data.
 * 3. The audit columns (created_by_id, updated_by_id) are also dead data
 *    until observer code populates them. A follow-up PR wires the
 *    `Auditable` trait.
 * 4. The FK constraints to `users.id` are NOT added in this migration —
 *    they are added by a dedicated FK migration (`...000050_add_audit_fks.php`)
 *    so that the order of operations is unambiguous and tests can verify
 *    each step.
 */
return new class extends Migration
{
    /**
     * Tables that get soft delete + audit.
     * ['table' => 'add_soft_delete']
     */
    private const SOFT_DELETE_TABLES = [
        'grs',
        'gatepasses',
        'challans',
        'frieghts',
        'truckdrivers',
    ];

    /**
     * Tables that get audit but NOT soft delete.
     * (lives with parent; audit trail lives on the parent)
     */
    private const AUDIT_ONLY_TABLES = [
        'challan_iteams',
    ];

    public function up(): void
    {
        foreach (self::SOFT_DELETE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->softDeletes();
                $table->unsignedBigInteger('created_by_id')->nullable()->after('deleted_at');
                $table->unsignedBigInteger('updated_by_id')->nullable()->after('created_by_id');
                $table->index('created_by_id', "idx_{$tableName}_created_by_id");
                $table->index('updated_by_id', "idx_{$tableName}_updated_by_id");
            });
        }

        foreach (self::AUDIT_ONLY_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('updated_by_id')->nullable();
                $table->index('created_by_id', "idx_{$tableName}_created_by_id");
                $table->index('updated_by_id', "idx_{$tableName}_updated_by_id");
            });
        }
    }

    public function down(): void
    {
        foreach (self::AUDIT_ONLY_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("idx_{$tableName}_created_by_id");
                $table->dropIndex("idx_{$tableName}_updated_by_id");
                $table->dropColumn(['created_by_id', 'updated_by_id']);
            });
        }

        foreach (self::SOFT_DELETE_TABLES as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropIndex("idx_{$tableName}_created_by_id");
                $table->dropIndex("idx_{$tableName}_updated_by_id");
                $table->dropColumn(['deleted_at', 'created_by_id', 'updated_by_id']);
            });
        }
    }
};
