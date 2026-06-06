<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Drop wrong UNIQUE constraints and add proper FK columns for traceability.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/ghost-field-audit.md  §4.1, §5.1, §3.8
 * - docs/relationship-map.md   §3.1, §3.2, §3.3, §3.4, §3.5
 * - docs/erd.md                §3 (Modernization diff summary)
 * - docs/master-execution-roadmap.md  §3.1–3.5, §6.1, §6.2
 * - docs/final-discovery-report.md  §6 (Ghost fields), §7.2 (Missing workflows)
 * - docs/erp-workflow-map.md   §2 (Gatepass), §3 (Challan)
 * - docs/business-workflows.md §2, §3
 * - docs/database-reconstruction-report.md §11.8
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the UNIQUE drops (documented bugs).
 *             100% on the new FK columns (declared in the target ERD).
 *             80%  on data-backfill values — depends on whether the legacy
 *                   gr_no / challan_no / truck_no strings are still present
 *                   and unique in the target table (see backfill query below).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr             (table: grs)
 * - App\Models\gatepass       (table: gatepasses)
 * - App\Models\challan        (table: challans)
 * - App\Models\ChallanItem    (table: challan_iteams)
 * - App\Models\Freight        (table: frieghts)
 * - App\Models\truckdriver    (table: truckdrivers)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController          (reads/writes grs)
 * - dash\GatepassController    (currently writes gr_no; will write gr_id in modernized flow)
 * - dash\ChallanController     (currently writes gr_no, challan_no; will write gr_id, challan_id, truck_id)
 * - dash\FreightController     (currently writes truck_no; will write truck_id)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. The dropped UNIQUE constraints (gatepasses.gr_no, challan_iteams.gr_no,
 *    truckdrivers.license, truckdrivers.mobile_no2) are not referenced by
 *    any application write path that would break. The new FK columns are
 *    NULLABLE, so existing rows are unaffected.
 * 2. truckdrivers.license UNIQUE is dropped (per docs/master-execution-roadmap.md
 *    §7.21). truck_no UNIQUE is preserved — that one is the business key.
 * 3. truckdrivers.mobile_no2 UNIQUE dropped (nullable UNIQUE is fragile;
 *    per docs/master-execution-roadmap.md §7.22).
 * 4. The new gr_id / challan_id / truck_id columns are nullable. Existing
 *    rows keep their string-based relationship. Application code that
 *    still reads/writes the string columns continues to work.
 * 5. A follow-up data-backfill migration (out of scope here) would populate
 *    gr_id / challan_id / truck_id from the string columns.
 *
 * --------------------------------------------------------------------------
 * Data backfill (for reference — executed by a separate one-shot script)
 * --------------------------------------------------------------------------
 * UPDATE gatepasses gp
 *   JOIN grs g ON g.gr_no = gp.gr_no
 *   SET gp.gr_id = g.id
 *   WHERE gp.gr_id IS NULL;
 *
 * UPDATE challan_iteams ci
 *   JOIN grs g       ON g.gr_no      = ci.gr_no
 *   JOIN challans c  ON c.challan_no = ci.challan_no
 *   SET ci.gr_id      = g.id,
 *       ci.challan_id = c.id
 *   WHERE ci.gr_id IS NULL OR ci.challan_id IS NULL;
 *
 * UPDATE challans c
 *   JOIN truckdrivers t ON t.truck_no = c.truck_no
 *   SET c.truck_id = t.id
 *   WHERE c.truck_id IS NULL;
 *
 * UPDATE frieghts f
 *   JOIN truckdrivers t ON t.truck_no = f.truck_no
 *   SET f.truck_id = t.id
 *   WHERE f.truck_id IS NULL;
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. gatepasses: drop wrong UNIQUE on gr_no; add gr_id (nullable FK)
        // ------------------------------------------------------------------
        Schema::table('gatepasses', function (Blueprint $table) {
            $table->dropUnique('gatepasses_gr_no_unique'); // legacy unique name
            $table->unsignedBigInteger('gr_id')->nullable()->after('gr_no');
            $table->index('gr_id', 'idx_gatepasses_gr_id');
        });

        // ------------------------------------------------------------------
        // 2. challan_iteams: drop wrong UNIQUE on gr_no; add gr_id + challan_id
        // ------------------------------------------------------------------
        Schema::table('challan_iteams', function (Blueprint $table) {
            $table->dropUnique('challan_iteams_gr_no_unique'); // legacy unique name
            $table->unsignedBigInteger('gr_id')->nullable()->after('gr_no');
            $table->unsignedBigInteger('challan_id')->nullable()->after('challan_no');
            $table->index('gr_id',      'idx_challan_iteams_gr_id');
            $table->index('challan_id', 'idx_challan_iteams_challan_id');
        });

        // ------------------------------------------------------------------
        // 3. challans: add proper integer id PK + truck_id (nullable FK)
        //    - The legacy PK is the string challan_no. We add an integer
        //      `id` as the new PK and keep challan_no UNIQUE for display.
        //    - The FK is added without a constraint here; constraints are
        //      added in a later migration (after the referenced table is
        //      guaranteed to exist and after backfill).
        // ------------------------------------------------------------------
        Schema::table('challans', function (Blueprint $table) {
            // Add a new auto-increment id (NULLable temporarily, made PK
            // below in a separate ALTER). We can't put `id` before
            // `challan_no` because the table already exists.
            if (! Schema::hasColumn('challans', 'id')) {
                $table->bigIncrements('id')->first();
            }
            $table->unsignedBigInteger('truck_id')->nullable()->after('truck_no');
            $table->index('truck_id', 'idx_challans_truck_id');
        });

        // Promote `id` to PRIMARY KEY (MySQL allows this even though
        // challan_no is the de-facto lookup key).
        // Guard: only do this if a primary key on `id` doesn't already exist.
        $pkCheck = DB::select(
            "SELECT COUNT(*) AS c
             FROM information_schema.table_constraints
             WHERE table_schema = DATABASE()
               AND table_name   = 'challans'
               AND constraint_type = 'PRIMARY KEY'
               AND constraint_name != 'PRIMARY'"
        );
        // The legacy table has no PK at all (just UNIQUE on challan_no).
        // Add a primary key on `id` only if one doesn't exist.
        $existingPk = DB::select(
            "SELECT COUNT(*) AS c
             FROM information_schema.table_constraints
             WHERE table_schema = DATABASE()
               AND table_name   = 'challans'
               AND constraint_type = 'PRIMARY KEY'"
        );
        if ($existingPk[0]->c == 0) {
            DB::statement('ALTER TABLE `challans` ADD PRIMARY KEY (`id`)');
        }

        // ------------------------------------------------------------------
        // 4. frieghts: add truck_id (nullable FK)
        // ------------------------------------------------------------------
        Schema::table('frieghts', function (Blueprint $table) {
            $table->unsignedBigInteger('truck_id')->nullable()->after('truck_no');
            $table->index('truck_id', 'idx_frieghts_truck_id');
        });

        // ------------------------------------------------------------------
        // 5. truckdrivers: drop wrong UNIQUE on license and mobile_no2
        // ------------------------------------------------------------------
        Schema::table('truckdrivers', function (Blueprint $table) {
            $table->dropUnique('truckdrivers_license_unique');    // wrong: blocks reassignment
            $table->dropUnique('truckdrivers_mobile_no2_unique'); // wrong: nullable UNIQUE
            // truck_no UNIQUE is preserved — that is the business key.
        });
    }

    public function down(): void
    {
        Schema::table('truckdrivers', function (Blueprint $table) {
            $table->unique('license',    'truckdrivers_license_unique');
            $table->unique('mobile_no2', 'truckdrivers_mobile_no2_unique');
        });

        Schema::table('frieghts', function (Blueprint $table) {
            $table->dropIndex('idx_frieghts_truck_id');
            $table->dropColumn('truck_id');
        });

        Schema::table('challans', function (Blueprint $table) {
            $table->dropIndex('idx_challans_truck_id');
            $table->dropColumn('truck_id');
        });

        Schema::table('challan_iteams', function (Blueprint $table) {
            $table->dropIndex('idx_challan_iteams_challan_id');
            $table->dropIndex('idx_challan_iteams_gr_id');
            $table->dropColumn(['challan_id', 'gr_id']);
            $table->unique('gr_no', 'challan_iteams_gr_no_unique');
        });

        Schema::table('gatepasses', function (Blueprint $table) {
            $table->dropIndex('idx_gatepasses_gr_id');
            $table->dropColumn('gr_id');
            $table->unique('gr_no', 'gatepasses_gr_no_unique');
        });
    }
};
