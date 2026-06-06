<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `audit_logs` table — append-only forensic record of changes.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11  (Missing tables — audit_logs)
 * - docs/erd.md                             §2  (AUDIT_LOG entity), §3
 * - docs/security-audit.md                  §12.2 (regulatory: 6-year GST
 *                                              retention; forensics
 *                                              impossible today)
 * - docs/refactoring-plan.md                §3.7  (audit observers design)
 * - docs/master-execution-roadmap.md        §2.2, §5.6, §11 (P0)
 * - docs/final-discovery-report.md          §13   (Gate 2)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% — table is in the modernized ERD with full schema.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\AuditLog (recommended)        (table: audit_logs)
 * - App\Models\User                              (FK: audit_logs.user_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) App\Services\AuditService               (writes here)
 * - (NEW) App\Observers\GrObserver                (uses AuditService)
 * - (NEW) App\Observers\GatepassObserver          (uses AuditService)
 * - (NEW) App\Observers\ChallanObserver           (uses AuditService)
 * - (NEW) App\Observers\FreightObserver           (uses AuditService)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The `user_id` column is NULLABLE; audit survives user deletion.
 * 3. The FK to `users.id` is declared as a constraint; the constraint
 *    uses `ON DELETE SET NULL` so an audit row is not deleted when a user
 *    is deleted. (A follow-up migration adds the FK; this migration just
 *    creates the column.)
 * 4. `old_values` / `new_values` are JSON columns. MySQL 5.7+ and MariaDB
 *    10.2+ support native JSON.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('auditable_type', 100);   // e.g. 'App\\Models\\Gr'
            $table->unsignedBigInteger('auditable_id');
            $table->string('event', 20);             // 'created', 'updated',
                                                    // 'deleted', 'restored'
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('url', 255)->nullable();
            $table->string('ip', 45)->nullable();    // IPv6 max length = 45
            $table->timestamp('created_at')->useCurrent();

            $table->index('user_id', 'idx_audit_logs_user_id');
            $table->index(['auditable_type', 'auditable_id'], 'idx_audit_logs_auditable');
            $table->index('event', 'idx_audit_logs_event');
            $table->index('created_at', 'idx_audit_logs_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
