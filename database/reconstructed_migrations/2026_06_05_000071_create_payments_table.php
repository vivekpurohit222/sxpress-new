<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `payments` table — customer receipts against a GR.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — payments)
 * - docs/erd.md                             §2  (PAYMENT entity)
 * - docs/business-workflows.md              §7  (customer payment workflow)
 * - docs/master-execution-roadmap.md        §2.8, §5.12
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\Payment (table: payments)
 * - App\Models\Gr           (FK: payments.gr_id)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\PaymentController
 * - dash\GrController        (links outstanding balance to payment list)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * New table — no overlap with any existing table.
 * Append-only — no soft delete; corrections are reversal payments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('gr_id');
            $table->date('paid_on');
            $table->decimal('amount', 12, 2);
            $table->string('method', 30);          // cash / cheque / NEFT / UPI / RTGS
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('gr_id', 'idx_payments_gr_id');
            $table->index('paid_on', 'idx_payments_paid_on');
            $table->index('method', 'idx_payments_method');
            $table->index('created_by_id', 'idx_payments_created_by_id');
            $table->index('updated_by_id', 'idx_payments_updated_by_id');

            $table->foreign('gr_id')->references('id')->on('grs')
                  ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
