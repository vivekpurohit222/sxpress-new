<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add performance + business-key indexes to frieghts, truckdrivers, and users.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/performance-report.md §3.1
 * - docs/erd.md §4.2
 * - docs/master-execution-roadmap.md §4.16–4.20
 * - docs/database-reconstruction-report.md §6.4, §9.4
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% (except frieghts.fm_no UNIQUE which is 90% — see notes)
 * --------------------------------------------------------------------------
 * The UNIQUE on frieghts.fm_no is a recommended business-key constraint. It
 * will FAIL on a populated database if duplicate fm_no values exist.
 * Mitigation: ship with a pre-check query (see implementation report §6).
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Freight       (table: frieghts — preserved misspelling)
 * - App\Models\truckdriver   (table: truckdrivers)
 * - App\Models\User          (table: users)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\FreightController       (filters by fm_no, truck_no, fm_date)
 * - TruckdriverController        (search by driver_name)
 * - UserController               (tenancy filter on office)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * - frieghts.fm_no UNIQUE is brand-new; if duplicates exist, this migration
 *   will fail. The implementation report includes a pre-check SQL.
 * - users.email UNIQUE is already present (legacy migration).
 * - All other indexes are additive.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('frieghts', function (Blueprint $table) {
            $table->unique('fm_no', 'uk_frieghts_fm_no');
            $table->index('fm_date',  'idx_frieghts_fm_date');
            $table->index('truck_no', 'idx_frieghts_truck_no');
            $table->index(['from_dest', 'to_dest'], 'idx_frieghts_from_to');
        });

        Schema::table('truckdrivers', function (Blueprint $table) {
            $table->index('driver_name', 'idx_truckdrivers_driver_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('office', 'idx_users_office');
        });
    }

    public function down(): void
    {
        Schema::table('frieghts', function (Blueprint $table) {
            $table->dropUnique('uk_frieghts_fm_no');
            $table->dropIndex('idx_frieghts_fm_date');
            $table->dropIndex('idx_frieghts_truck_no');
            $table->dropIndex('idx_frieghts_from_to');
        });

        Schema::table('truckdrivers', function (Blueprint $table) {
            $table->dropIndex('idx_truckdrivers_driver_name');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('idx_users_office');
        });
    }
};
