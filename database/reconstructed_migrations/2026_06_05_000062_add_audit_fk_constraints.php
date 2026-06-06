<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promote the `created_by_id` / `updated_by_id` columns to FK constraints
 * referencing `users.id`, and add the FK from `audit_logs.user_id` to
 * `users.id`.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/erd.md                             §6  (Audit columns: created_by_id
 *                                              → users, updated_by_id → users)
 * - docs/database-reconstruction-report.md  §11 (audit_logs table)
 * - docs/refactoring-plan.md                §3.7  (audit observers)
 * - docs/master-execution-roadmap.md        §3.17
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on FK list and nullability.
 *             ON DELETE SET NULL is the standard pattern: audit survives
 *             user deletion.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr             (created_by_id, updated_by_id)
 * - App\Models\gatepass       (created_by_id, updated_by_id)
 * - App\Models\challan        (created_by_id, updated_by_id)
 * - App\Models\ChallanItem    (created_by_id, updated_by_id)
 * - App\Models\Freight        (created_by_id, updated_by_id)
 * - App\Models\truckdriver    (created_by_id, updated_by_id)
 * - App\Models\AuditLog       (user_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) App\Observers\* (write created_by_id / updated_by_id)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. ON DELETE SET NULL — when a user is hard-deleted, the audit columns
 *    on rows they created are set to NULL. The row itself is preserved.
 * 2. All columns are already NULLABLE (added in migration 000030); this
 *    migration only adds the constraint, not the columns.
 */
return new class extends Migration
{
    /** Tables that have created_by_id / updated_by_id columns. */
    private const TABLES = [
        'grs',
        'gatepasses',
        'challans',
        'challan_iteams',
        'frieghts',
        'truckdrivers',
        'customers',
        'vendors',
        'branches',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'created_by_id')) {
                $this->addFkIfMissing($table, 'created_by_id', 'fk_' . $table . '_created_by_id');
            }
            if (Schema::hasColumn($table, 'updated_by_id')) {
                $this->addFkIfMissing($table, 'updated_by_id', 'fk_' . $table . '_updated_by_id');
            }
        }

        // audit_logs.user_id
        if (Schema::hasTable('audit_logs') && Schema::hasColumn('audit_logs', 'user_id')) {
            $this->addFkIfMissing('audit_logs', 'user_id', 'fk_audit_logs_user_id');
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            if (Schema::hasColumn($table, 'created_by_id')) {
                $this->dropFkIfExists($table, 'fk_' . $table . '_created_by_id');
            }
            if (Schema::hasColumn($table, 'updated_by_id')) {
                $this->dropFkIfExists($table, 'fk_' . $table . '_updated_by_id');
            }
        }
        if (Schema::hasTable('audit_logs')) {
            $this->dropFkIfExists('audit_logs', 'fk_audit_logs_user_id');
        }
    }

    private function addFkIfMissing(string $table, string $column, string $constraint): void
    {
        $exists = \DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM information_schema.table_constraints
              WHERE table_schema     = DATABASE()
                AND table_name       = ?
                AND constraint_name  = ?",
            [$table, $constraint]
        );
        if ($exists && $exists->c > 0) {
            return;
        }
        \DB::statement(
            "ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` "
            . "FOREIGN KEY (`{$column}`) REFERENCES `users` (`id`) "
            . "ON DELETE SET NULL ON UPDATE CASCADE"
        );
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        $exists = \DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM information_schema.table_constraints
              WHERE table_schema     = DATABASE()
                AND table_name       = ?
                AND constraint_name  = ?",
            [$table, $constraint]
        );
        if (! $exists || $exists->c === 0) {
            return;
        }
        \DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
    }
};
