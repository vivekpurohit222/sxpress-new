<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `vendors` table — first-class truck owner master.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — vendors)
 * - docs/erd.md                             §2  (VENDOR entity), §3
 *                                              (trucks.owner_vendor_id)
 * - docs/relationship-map.md                §3.13 (FK to trucks)
 * - docs/business-workflows.md              §6  (settlement / bank details)
 * - docs/master-execution-roadmap.md        §2.5, §5.9
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% — table is in the modernized ERD.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Vendor (recommended)          (table: vendors)
 * - (FUTURE) App\Models\Truck                      (FK: trucks.owner_vendor_id,
 *                                                  added when truckdrivers
 *                                                  is split)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\VendorController                    (CRUD)
 * - dash\ChallanController                         (selects vendor on
 *                                                    challan create)
 * - dash\FreightController                         (selects vendor on FM
 *                                                    create)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The free-text `challans.owner_name` and `truckdrivers.license` are
 *    preserved as-is. The future `trucks.owner_vendor_id` FK is nullable.
 * 3. A backfill SQL (out of scope) populates `vendors` from
 *    `SELECT DISTINCT owner_name FROM challans`.
 * 4. `bank_account` and `ifsc` support settlement payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();    // e.g. 'V0001'
            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->string('gst_no', 15)->nullable();
            $table->string('pan_no', 10)->nullable();
            $table->string('bank_account', 30)->nullable();
            $table->string('ifsc', 15)->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('gst_no', 'idx_vendors_gst_no');
            $table->index('is_active', 'idx_vendors_is_active');
            $table->index('name', 'idx_vendors_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
