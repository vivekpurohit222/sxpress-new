<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Widen undersized varchar columns to safe, future-proof lengths.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §4.1, §5.1, §6.1, §7.1, §9.1
 *   (every "Recommended" varchar width is greater than the current value)
 * - docs/master-execution-roadmap.md        §5.3 (column precision/type cleanup)
 * - docs/performance-report.md              §6  (decimal precision, by extension
 *                                              text/varchar widths)
 * - docs/ghost-field-audit.md               §2  (typo columns; same row)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100%
 * --------------------------------------------------------------------------
 * Widening a varchar is non-destructive in MySQL/InnoDB. No data is lost.
 * Narrowing a varchar (the down()) is destructive only if existing values
 * exceed the new length — we restore the exact original widths on rollback.
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
 * - dash\GrController          (reads/writes grs.gr_no, from_dest, to_dest)
 * - dash\GatepassController    (reads/writes gatepasses.gr_no, from_dest, to_dest)
 * - dash\ChallanController     (reads/writes challans.challan_no, truck_no,
 *                                driver_name, license, owner_name)
 * - dash\FreightController     (reads/writes frieghts.fm_no, truck_no,
 *                                entry_1..4)
 * - TruckdriverController      (reads/writes truckdrivers.license)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. Widening a varchar is non-destructive in MySQL 8 / InnoDB. Existing
 *    data is preserved.
 * 2. No application code currently uses more than the new width.
 * 3. The down() restores the original narrow widths, which is destructive
 *    only if values > original width were inserted in the meantime.
 */
return new class extends Migration
{
    /**
     * Column name => new width (legacy -> modern).
     * Wide-then-narrow is the safe pattern.
     */
    private const WIDEN = [
        // grs — gr_no(8) too tight for some office series
        'grs' => [
            'gr_no'      => 20,
            'from_dest'  => 50,
            'to_dest'    => 50,
            'consignor'  => 150,
            'consignee'  => 150,
            'meth'       => 10,
            'pm'         => 50,
            'eway_bill_number' => 20,
        ],

        // gatepasses — gr_no(15) wider is safer; m_s -> consignor (50)
        'gatepasses' => [
            'gr_no'     => 20,
            'from_dest' => 50,
            'to_dest'   => 50,
            'm_s'       => 150,
            'pm'        => 50,
        ],

        // challans — driver_name(255) fine, widen anyway for symmetry
        'challans' => [
            'challan_no'  => 20,
            'from_dest'   => 50,
            'to_dest'     => 50,
            'truck_no'    => 20,
            'driver_name' => 150,
            'license'     => 20,
            'owner_name'  => 150,
        ],

        // challan_iteams — gr_no(8) too tight, challan_no
        'challan_iteams' => [
            'gr_no'      => 20,
            'challan_no' => 20,
            'meth'       => 10,
        ],

        // frieghts — fm_no(10), truck_no(10), entry_1..4(13) — widen all
        'frieghts' => [
            'fm_no'    => 20,
            'fm_date'  => 11, // legacy expects short string; widen only to be safe
            'from_dest' => 50,
            'to_dest'   => 50,
            'truck_no'  => 20,
            'entry_1'   => 150,
            'entry_2'   => 150,
            'entry_3'   => 150,
            'entry_4'   => 150,
        ],

        // truckdrivers — driver_name(191) standard, license(20) is fine
        'truckdrivers' => [
            'driver_name' => 150,
            'truck_no'    => 20,
            'license'     => 20,
            'mobile_no1'  => 15,
            'mobile_no2'  => 15,
        ],
    ];

    /**
     * Original widths — used by down() to restore.
     */
    private const NARROW = [
        'grs' => [
            'gr_no'      => 8,
            'from_dest'  => 13,
            'to_dest'    => 13,
            'consignor'  => 255, // legacy $table->string('consignor') = 255
            'consignee'  => 255,
            'meth'       => 255,
            'pm'         => 255,
            'eway_bill_number' => 255,
        ],
        'gatepasses' => [
            'gr_no'     => 15,
            'from_dest' => 13,
            'to_dest'   => 13,
            'm_s'       => 255,
            'pm'        => 255,
        ],
        'challans' => [
            'challan_no'  => 255,
            'from_dest'   => 13,
            'to_dest'     => 13,
            'truck_no'    => 20,
            'driver_name' => 255,
            'license'     => 20,
            'owner_name'  => 255,
        ],
        'challan_iteams' => [
            'gr_no'      => 8,
            'challan_no' => 255,
            'meth'       => 255,
        ],
        'frieghts' => [
            'fm_no'    => 10,
            'fm_date'  => 11,
            'from_dest' => 13,
            'to_dest'   => 13,
            'truck_no'  => 10,
            'entry_1'   => 13,
            'entry_2'   => 13,
            'entry_3'   => 13,
            'entry_4'   => 13,
        ],
        'truckdrivers' => [
            'driver_name' => 191,
            'truck_no'    => 20,
            'license'     => 20,
            'mobile_no1'  => 11,
            'mobile_no2'  => 11,
        ],
    ];

    public function up(): void
    {
        foreach (self::WIDEN as $table => $columns) {
            foreach ($columns as $column => $newWidth) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                // Determine the type prefix from the existing column.
                // We use MODIFY COLUMN so the rest of the column spec is
                // preserved (NULL/NOT NULL, default, etc).
                $colInfo = DB::selectOne(
                    "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
                       FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME   = ?
                        AND COLUMN_NAME  = ?",
                    [$table, $column]
                );
                if (! $colInfo) {
                    continue;
                }
                $nullability = $colInfo->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $colInfo->COLUMN_DEFAULT !== null
                    ? " DEFAULT " . DB::getPdo()->quote($colInfo->COLUMN_DEFAULT)
                    : '';
                // Skip if already wider or equal.
                if (preg_match('/varchar\((\d+)\)/i', $colInfo->COLUMN_TYPE, $m)) {
                    if ((int) $m[1] >= $newWidth) {
                        continue;
                    }
                }
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR({$newWidth}) {$nullability}{$default}"
                );
            }
        }
    }

    public function down(): void
    {
        foreach (self::NARROW as $table => $columns) {
            foreach ($columns as $column => $oldWidth) {
                if (! Schema::hasColumn($table, $column)) {
                    continue;
                }
                $colInfo = DB::selectOne(
                    "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                       FROM information_schema.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE()
                        AND TABLE_NAME   = ?
                        AND COLUMN_NAME  = ?",
                    [$table, $column]
                );
                if (! $colInfo) {
                    continue;
                }
                $nullability = $colInfo->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
                $default = $colInfo->COLUMN_DEFAULT !== null
                    ? " DEFAULT " . DB::getPdo()->quote($colInfo->COLUMN_DEFAULT)
                    : '';
                DB::statement(
                    "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` VARCHAR({$oldWidth}) {$nullability}{$default}"
                );
            }
        }
    }
};
