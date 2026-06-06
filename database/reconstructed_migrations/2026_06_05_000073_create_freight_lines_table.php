<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `freight_lines` table — normalizes the legacy 4-column-wide
 * `frieghts.entry_1..4` pattern into a proper 1-to-many relationship.
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §6.3 (4 numbered entries — should
 *                                              be a separate table)
 * - docs/erd.md                             §2  (FREIGHT_LINE entity)
 * - docs/master-execution-roadmap.md        §2.10, §5.14
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% on the schema.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\FreightLine (table: freight_lines)
 * - (FUTURE) App\Models\FreightMemo (table: freight_memos; in this
 *           migration we FK to frieghts (legacy spelling) so the
 *           backfill can populate immediately.)
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\FreightController  (renders the lines on FM show)
 * - (NEW) dash\FreightLineController
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The legacy entry_1..4 columns on frieghts are preserved (denormalized
 *    snapshot for print).
 * 3. A backfill (out of scope) populates freight_lines from
 *    frieghts.entry_1..4:
 *
 *      INSERT INTO freight_lines (freight_memo_id, sequence, description, amount)
 *      SELECT id, 1, entry_1, entry_1_amount FROM frieghts
 *        WHERE entry_1 IS NOT NULL AND entry_1 <> '';
 *      -- repeat for sequence 2/3/4
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freight_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('freight_memo_id');
            $table->unsignedSmallInteger('sequence');
            $table->string('description', 150)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamps();

            $table->index('freight_memo_id', 'idx_freight_lines_freight_memo_id');
            $table->unique(['freight_memo_id', 'sequence'], 'uk_freight_lines_memo_sequence');

            $table->foreign('freight_memo_id')->references('id')->on('frieghts')
                  ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freight_lines');
    }
};
