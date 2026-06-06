<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Create the `number_sequences` table — replaces the fragile per-controller
 * counter logic (`Model::latest()->first()->xxx_no; $xxx_no++;`).
 *
 * --------------------------------------------------------------------------
 * Source of discovery
 * --------------------------------------------------------------------------
 * - docs/database-reconstruction-report.md  §5.3, §6.3, §7.3, §8.3
 *                                              (every doc mentions the
 *                                              fragile counter pattern)
 * - docs/erd.md                             §2  (NUMBER_SEQUENCE entity)
 * - docs/performance-report.md              §8.2, §8.3, §8.4
 *                                              (the counters race; first
 *                                              come first served)
 * - docs/refactoring-plan.md                §3.1  (NumberSequenceService
 *                                              design)
 * - docs/master-execution-roadmap.md        §2.3, §5.4, §11 (P0)
 * - docs/erp-workflow-map.md                §2, §3, §4, §5  (every per-office
 *                                              counter)
 *
 * --------------------------------------------------------------------------
 * Confidence: 100% — table is in the modernized ERD.
 * --------------------------------------------------------------------------
 *
 * --------------------------------------------------------------------------
 * Related models
 * --------------------------------------------------------------------------
 * - (NEW) App\Models\NumberSequence (recommended)  (table: number_sequences)
 * - App\Models\Gr             (consumes scope 'gr')
 * - App\Models\gatepass       (consumes scope 'gp')
 * - App\Models\challan        (consumes scope 'challan')
 * - App\Models\Freight        (consumes scope 'fm')
 *
 * --------------------------------------------------------------------------
 * Related controllers
 * --------------------------------------------------------------------------
 * - dash\GrController         (uses scope 'gr:<branch_code>' e.g. 'gr:RJKT')
 * - dash\GatepassController   (uses scope 'gp')
 * - dash\ChallanController    (uses scope 'challan')
 * - dash\FreightController    (uses scope 'fm')
 *
 * --------------------------------------------------------------------------
 * Backward compatibility
 * --------------------------------------------------------------------------
 * 1. New table — no overlap with any existing table.
 * 2. The existing per-controller counter logic still works. The new
 *    `NumberSequenceService` is opt-in.
 * 3. A seeder (out of scope for this migration) inserts the 4 baseline
 *    rows with `current_value = 0`. The service increments them
 *    atomically (`SELECT ... FOR UPDATE`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->bigIncrements('id');
            // Scope: free-form. Examples: 'gr', 'gp', 'challan', 'fm',
            // 'gr:RJKT' (per-office), 'gr:KASH', etc.
            $table->string('scope', 50)->unique();
            $table->unsignedBigInteger('current_value')->default(0);
            $table->string('prefix', 20)->nullable();   // e.g. 'AA-'
            $table->unsignedSmallInteger('pad_length')->default(5); // '00001'
            $table->timestamp('updated_at')->nullable();

            $table->index('scope', 'idx_number_sequences_scope');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
