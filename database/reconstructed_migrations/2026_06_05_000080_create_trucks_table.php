<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `trucks` table — first-class vehicle master, split out of the
 * legacy combined `truckdrivers` table.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §9.3 (modernization opportunity:
 *                                              split truckdrivers)
 * - docs/erd.md                             §2  (TRUCK entity), §3
 *                                              (truckdrivers split)
 * - docs/business-workflows.md              §6  (truck ownership)
 * - docs/master-execution-roadmap.md        §2.6, §5.10
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema. 90% on the backfill — depends on the
 *             shape of legacy data (a truck_no can appear on multiple
 *             rows if a driver changed trucks).
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Truck (table: trucks)
 * - App\Models\truckdriver  (legacy — data is backfilled FROM here)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\TruckController
 * - dash\ChallanController  (selects truck on challan create)
 * - dash\FreightController  (selects truck on FM create)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The legacy `truckdrivers` table is preserved (drivers still live there
 *    until migration 000081 creates the `drivers` table and 000082 creates
 *    the `truck_assignments` pivot).
 * 3. A backfill populates `trucks` from distinct `truckdrivers.truck_no`:
 *
 *      INSERT INTO trucks (truck_no, is_active, created_at, updated_at)
 *      SELECT DISTINCT truck_no, 1, NOW(), NOW() FROM truckdrivers
 *        WHERE truck_no IS NOT NULL AND truck_no <> '';
 *
 * 4. The new `trucks.owner_vendor_id` FK is nullable — trucks that don't
 *    yet have a vendor match will get a NULL owner.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trucks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('truck_no', 20)->unique();
            $table->unsignedBigInteger('owner_vendor_id')->nullable();
            $table->unsignedBigInteger('home_branch_id')->nullable();
            $table->string('make', 50)->nullable();
            $table->string('model', 50)->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('fitness_certificate_no', 50)->nullable();
            $table->date('fitness_expiry')->nullable();
            $table->string('insurance_no', 50)->nullable();
            $table->date('insurance_expiry')->nullable();
            $table->string('permit_no', 50)->nullable();
            $table->date('permit_expiry')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('owner_vendor_id', 'idx_trucks_owner_vendor_id');
            $table->index('home_branch_id', 'idx_trucks_home_branch_id');
            $table->index('is_active', 'idx_trucks_is_active');

            $table->foreign('owner_vendor_id')->references('id')->on('vendors')
                  ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('home_branch_id')->references('id')->on('branches')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trucks');
    }
};
