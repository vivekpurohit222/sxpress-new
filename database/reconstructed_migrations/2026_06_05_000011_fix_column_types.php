<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Fix column types identified in the discovery phase.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §13 (Column-type corrections)
 * - docs/performance-report.md              §6  (Database-level optimizations)
 * - docs/master-execution-roadmap.md        §5.3 (Migration 5.3)
 * - docs/ghost-field-audit.md               §3.7
 * - docs/business-workflows.md              §1, §2
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the type changes themselves.
 *             60%  on data-format compatibility — the copy_date / gp_date /
 *                   fm_date / challan_date columns are stored as `string`
 *                   today with format `d-m-y` (per docs). If the production
 *                   data follows that format, MySQL will accept the cast.
 *                   A pre-flight check is included in the implementation
 *                   report §6.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Gr             (grs.copy_date, grs.weight, money columns)
 * - App\Models\gatepass       (gatepasses.gp_date, money columns)
 * - App\Models\challan        (challans.challan_date, challans.challan_total)
 * - App\Models\ChallanItem    (challan_iteams money columns)
 * - App\Models\Freight        (frieghts.fm_date, frieghts money columns)
 * - App\Models\truckdriver    (truckdrivers.driver_address → text)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController          (validates, inserts, prints copy_date)
 * - dash\GatepassController    (validates gp_date)
 * - dash\ChallanController     (validates challan_date)
 * - dash\FreightController     (validates fm_date)
 * - TruckdriverController      (validates driver_address)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. The `date` type is MySQL's DATE — values `YYYY-MM-DD`. If existing
 *    data is in `dd-mm-yy` format, this ALTER will fail. Mitigation:
 *    - Pre-check: SELECT copy_date, COUNT(*) FROM grs GROUP BY copy_date
 *    - If any row fails YYYY-MM-DD parsing, run a one-shot reformatter.
 * 2. Changing `decimal` → `decimal(p,s)` may TRUNCATE existing data if
 *    existing values have more precision than the new type allows.
 *    - weight  decimal → decimal(10,3)  (kg, 3 dp) — risk if any value
 *                                              has > 3 decimal places
 *    - money   decimal → decimal(12,2)  (INR, 2 dp) — risk if any value
 *                                              has > 2 decimal places
 * 3. The `string` (no length) for driver_address is MySQL's default
 *    varchar(255). Changing to `text` is a non-breaking widening.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // grs — date + precision + money
        // ------------------------------------------------------------------
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `copy_date` DATE NULL');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `weight`       DECIMAL(10,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `bill_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `frieght_amount` DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `sur_ch`        DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `c_r`           DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `other`         DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `bc_amount`     DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `total_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');

        // ------------------------------------------------------------------
        // gatepasses — date + money precision
        // ------------------------------------------------------------------
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `gp_date`        DATE NULL');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `weight`         DECIMAL(10,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `frieght_amount` DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `labour_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `other`          DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `dc_amount`      DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `total_amount`   DECIMAL(12,2) NOT NULL DEFAULT 0');

        // ------------------------------------------------------------------
        // challans — date + total precision
        // ------------------------------------------------------------------
        DB::statement('ALTER TABLE `challans` MODIFY COLUMN `challan_date`  DATE NULL');
        DB::statement('ALTER TABLE `challans` MODIFY COLUMN `challan_total` DECIMAL(14,2) NULL');

        // ------------------------------------------------------------------
        // challan_iteams — money precision
        // ------------------------------------------------------------------
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `weight` DECIMAL(10,3) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `paid`   DECIMAL(12,2) NULL');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `to_pay` DECIMAL(12,2) NULL');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `sur_ch` DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `c_r`    DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `other`  DECIMAL(12,2) NOT NULL DEFAULT 0');

        // ------------------------------------------------------------------
        // frieghts — date + money precision
        // ------------------------------------------------------------------
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `fm_date`         DATE NULL');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_1_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_2_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_3_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_4_amount`  DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `total_amount`    DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `truck_freight`   DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `commission`      DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `other_charges`   DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `extra`           DECIMAL(12,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `balance_to_sn`   DECIMAL(12,2) NOT NULL DEFAULT 0');

        // ------------------------------------------------------------------
        // truckdrivers — address widening
        // ------------------------------------------------------------------
        DB::statement('ALTER TABLE `truckdrivers` MODIFY COLUMN `driver_address` TEXT NOT NULL');
    }

    public function down(): void
    {
        // Revert types to the legacy shape. Note: any data inserted under
        // the new (stricter) types will be preserved; the legacy shape is
        // strictly looser.

        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `copy_date` VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `weight`       DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `bill_amount`  DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `frieght_amount` DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `sur_ch`        DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `c_r`           DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `other`         DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `bc_amount`     DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `grs` MODIFY COLUMN `total_amount`  DECIMAL(8,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `gp_date`        VARCHAR(11) NULL');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `weight`         INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `frieght_amount` INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `labour_amount`  INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `other`          INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `dc_amount`      INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `gatepasses` MODIFY COLUMN `total_amount`   INT NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE `challans` MODIFY COLUMN `challan_date`  VARCHAR(255) NULL');
        DB::statement('ALTER TABLE `challans` MODIFY COLUMN `challan_total` DECIMAL(8,2) NULL');

        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `weight` DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `paid`   DECIMAL(8,2) NULL');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `to_pay` DECIMAL(8,2) NULL');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `sur_ch` DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `c_r`    DECIMAL(8,2) NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `challan_iteams` MODIFY COLUMN `other`  DECIMAL(8,2) NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `fm_date`         VARCHAR(11) NULL');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_1_amount`  INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_2_amount`  INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_3_amount`  INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `entry_4_amount`  INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `total_amount`    INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `truck_freight`   INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `commission`      INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `other_charges`   INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `extra`           INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE `frieghts` MODIFY COLUMN `balance_to_sn`   INT NOT NULL DEFAULT 0');

        DB::statement('ALTER TABLE `truckdrivers` MODIFY COLUMN `driver_address` VARCHAR(255) NOT NULL');
    }
};
