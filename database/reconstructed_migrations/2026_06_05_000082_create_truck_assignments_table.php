<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `truck_assignments` table — pivot between `trucks` and
 * `drivers` (a driver can be reassigned to a different truck over time;
 * a truck can have multiple drivers over time).
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §9.3
 * - docs/erd.md                             §2  (TRUCK_ASSIGNMENT entity)
 * - docs/master-execution-roadmap.md        §2.6, §5.10
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema. 90% on the backfill — legacy data has
 *             no assignment dates; the backfill inserts a single open-ended
 *             row per (truck_no, license) pair.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - App\Models\Truck   (FK: truck_assignments.truck_id)
 * - App\Models\Driver  (FK: truck_assignments.driver_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\TruckAssignmentController
 * - dash\TruckController  (assignments tab)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The pivot supports an open-ended assignment (`assigned_to IS NULL`).
 * 3. The composite unique (truck_id, driver_id, assigned_from) prevents
 *    accidentally assigning the same driver to the same truck on the
 *    same day twice.
 * 4. ON DELETE CASCADE — if either the truck or the driver is hard-deleted,
 *    the assignment is removed (a soft delete is the policy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('truck_assignments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('truck_id');
            $table->unsignedBigInteger('driver_id');
            $table->date('assigned_from');
            $table->date('assigned_to')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->unique(['truck_id', 'driver_id', 'assigned_from'], 'uk_truck_assignments');
            $table->index('driver_id', 'idx_truck_assignments_driver_id');
            $table->index('assigned_to', 'idx_truck_assignments_assigned_to');

            $table->foreign('truck_id')->references('id')->on('trucks')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('driver_id')->references('id')->on('drivers')
                  ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('truck_assignments');
    }
};
