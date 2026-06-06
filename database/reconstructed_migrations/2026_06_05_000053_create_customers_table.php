<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `customers` table — first-class consignor / consignee master.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — customers)
 * - docs/erd.md                             §2  (CUSTOMER entity), §3
 *                                              (grs.consignor_id, consignee_id)
 * - docs/relationship-map.md                §3.12 (FKs to grs)
 * - docs/business-workflows.md              §9  (customer GST reconciliation)
 * - docs/master-execution-roadmap.md        §2.4, §5.8
 * - docs/refactoring-plan.md                §3.4  (consolidate consignor /
 *                                              consignee)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% — table is in the modernized ERD.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Customer (recommended)       (table: customers)
 * - App\Models\Gr                                (FK: grs.consignor_id,
 *                                                 grs.consignee_id — both
 *                                                 nullable, set by
 *                                                 ...add_customer_fks.php)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\CustomerController                (CRUD)
 * - dash\GrController                            (selects customer by
 *                                                  gst_no in the create
 *                                                  form)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The free-text `grs.consignor` / `grs.consignee` / `grs.consignor_gst_no`
 *    / `grs.consignee_gst_no` columns are preserved as-is. The new
 *    `consignor_id` / `consignee_id` FKs are nullable (added by
 *    `...add_customer_fks.php`) so existing rows continue to work.
 * 3. A backfill SQL (out of scope) populates `customers` from
 *    `SELECT DISTINCT consignor, consignee FROM grs` and sets the FKs.
 * 4. `code` is auto-generated (C0001, C0002, …) by a model observer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('code', 20)->unique();    // e.g. 'C0001'
            $table->string('name', 150);
            $table->text('address')->nullable();
            $table->string('gst_no', 15)->nullable();
            $table->string('pan_no', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('gst_no', 'idx_customers_gst_no');
            $table->index('is_active', 'idx_customers_is_active');
            $table->index('name', 'idx_customers_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
