<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `drivers` table — first-class driver master, split out of the
 * legacy combined `truckdrivers` table.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §9.3 (split truckdrivers)
 * - docs/erd.md                             §2  (DRIVER entity)
 * - docs/master-execution-roadmap.md        §2.6, §5.10
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema. 90% on the backfill (the same driver
 *             may be duplicated across multiple truck rows today; the
 *             backfill dedupes by license).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Driver (table: drivers)
 * - App\Models\truckdriver (legacy — data backfilled FROM here)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\DriverController
 * - TruckdriverController   (legacy — remains in place)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The legacy `truckdrivers` table is preserved.
 * 3. A backfill populates `drivers` from distinct truckdrivers rows
 *    deduped by license:
 *
 *      INSERT INTO drivers (name, license, address, mobile1, mobile2,
 *                           is_active, created_at, updated_at)
 *      SELECT driver_name, license, driver_address, mobile_no1, mobile_no2,
 *             1, NOW(), NOW()
 *      FROM truckdrivers
 *      GROUP BY license;
 *
 * 4. License UNIQUE in the modernized table — but legacy truckdrivers has
 *    its UNIQUE on license dropped in migration 000010.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 150);
            $table->string('license', 20)->unique();
            $table->text('address')->nullable();
            $table->string('mobile1', 15)->nullable();
            $table->string('mobile2', 15)->nullable();
            $table->date('license_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('name', 'idx_drivers_name');
            $table->index('is_active', 'idx_drivers_is_active');
            $table->index('mobile1', 'idx_drivers_mobile1');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
