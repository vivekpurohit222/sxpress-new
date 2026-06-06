<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `branches` table — the canonical first-class entity that
 * replaces the hard-coded `users.office` string.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §2.3, §2.4, §11 (Missing tables)
 * - docs/erd.md                             §2  (BRANCH entity in modernized
 *                                              ERD), §3 (users.office →
 *                                              users.branch_id)
 * - docs/relationship-map.md                §2, §3.6, §3.7, §3.8, §3.9, §3.10,
 *                                              §3.11 (every from_dest/to_dest
 *                                              becomes a branches FK)
 * - docs/master-execution-roadmap.md        §2.1 (P0 missing tables)
 * - docs/erp-workflow-map.md                §2 (per-office GR numbering
 *                                              implies per-branch state)
 * - docs/security-audit.md                  §3.1  (tenancy model)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% — table is documented in the modernized ERD with full
 *             schema.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Branch (recommended)        (table: branches)
 * - App\Models\User                              (FK: users.branch_id)
 * - App\Models\Gr                                (FK: grs.from_branch_id,
 *                                                 grs.to_branch_id)
 * - App\Models\gatepass                          (FK: gatepasses.from_branch_id,
 *                                                 gatepasses.to_branch_id)
 * - App\Models\challan                           (FK: challans.from_branch_id,
 *                                                 challans.to_branch_id)
 * - App\Models\Freight                           (FK: frieghts.from_branch_id,
 *                                                 frieghts.to_branch_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController          (per-office GR list; will filter by branch_id)
 * - dash\GatepassController    (per-office GP list; will filter by branch_id)
 * - dash\ChallanController     (per-office challan list; will filter by
 *                                branch_id)
 * - dash\FreightController     (per-office FM list; will filter by branch_id)
 * - UserController             (users now have branch_id; CRUD will populate
 *                                the dropdown)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. `users.office` (string) is preserved as-is. A follow-up migration
 *    (`...add_branch_fks.php`) adds the nullable `branch_id` FK on every
 *    consumer table.
 * 2. Seeder inserts the 7 known offices (Kashmore Gate, Rajkot, Dayabasti,
 *    Swarup Nagar, Navagam, Shapar (1), Shapar (2)) so a backfill of
 *    `users.branch_id` and `grs.from_branch_id` from the existing `office`
 *    / `from_dest` strings is trivial.
 * 3. New table — no overlap with any existing table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();   // e.g. 'RJKT', 'KASH', 'NVGM'
            $table->string('name', 100);            // e.g. 'Rajkot'
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('pincode', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('is_active', 'idx_branches_is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
