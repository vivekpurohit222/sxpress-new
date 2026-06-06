<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `freight_payments` table — settlement payments to truck
 * owners against a freight memo.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §11 (Missing tables — freight_payments)
 * - docs/erd.md                             §2  (FREIGHT_PAYMENT entity)
 * - docs/business-workflows.md              §6  (settlement)
 * - docs/master-execution-roadmap.md        §2.9, §5.13
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\FreightPayment (table: freight_payments)
 * - (FUTURE) App\Models\FreightMemo (table: freight_memos; in this
 *           migration we FK to frieghts (legacy spelling) so the
 *           settlement workflow can be wired up immediately. A
 *           follow-up migration (000130) renames the table and
 *           the FK is updated.)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - (NEW) dash\FreightPaymentController
 * - dash\FreightController                  (links to settlement)
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * New table. FK points to frieghts.id (legacy table name). When the
 * table is renamed to freight_memos by migration 000130, the FK follows
 * (MySQL 8 RENAME updates FK metadata automatically).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freight_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('freight_memo_id');
            $table->date('paid_on');
            $table->decimal('amount', 12, 2);
            $table->string('method', 30);          // cash / cheque / NEFT / UPI
            $table->string('reference', 100)->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->unsignedBigInteger('updated_by_id')->nullable();
            $table->timestamps();

            $table->index('freight_memo_id', 'idx_freight_payments_freight_memo_id');
            $table->index('paid_on', 'idx_freight_payments_paid_on');
            $table->index('method', 'idx_freight_payments_method');
            $table->index('created_by_id', 'idx_freight_payments_created_by_id');
            $table->index('updated_by_id', 'idx_freight_payments_updated_by_id');

            $table->foreign('freight_memo_id')->references('id')->on('frieghts')
                  ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('created_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('updated_by_id')->references('id')->on('users')
                  ->onDelete('set null')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freight_payments');
    }
};
