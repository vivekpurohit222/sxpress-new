<?php

namespace Database\Seeders;

use App\Models\ChallanItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the legacy `challan_iteams` (Challan Items / Lines) table with
 * realistic demo data.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_12_06_102452_create_challan_iteams_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000010_add_fk_columns_and_drop_wrong_uniques.php
 *   (adds gr_id + challan_id FKs)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. For each challan, picks 1–6 random GRs and inserts one
 *    challan_iteam per (challan, gr) pair.
 *
 * 2. The line copies weight, nugs, meth, description, sur_ch, c_r,
 *    other from the GR — i.e. it is a denormalized snapshot at the
 *    moment of dispatch (per docs/database-reconstruction-report.md
 *    §8.3).
 *
 * 3. Wires `gr_id` and `challan_id` (modernized FKs) when the
 *    columns exist.
 *
 * 4. Recomputes `challans.challan_total` from the lines it inserts
 *    so the totals on the parent match the sum of the lines.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `(challan_id, gr_id)` (modernized) or by
 * `(challan_no, gr_no)` (legacy). Re-running the seeder updates
 * existing rows but does not duplicate.
 *
 * If the parent `challans` table has fewer than 1 row, the seeder
 * warns and exits early (run `ChallanSeeder` first).
 */
class ChallanItemSeeder extends Seeder
{
    /**
     * Min and max number of lines per challan.
     */
    private const LINES_PER_CHALLAN_MIN = 1;
    private const LINES_PER_CHALLAN_MAX = 6;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('challan_iteams')) {
            $this->command?->warn('[ChallanItemSeeder] Skipped — the `challan_iteams` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $challans = Schema::hasTable('challans') ? DB::table('challans')->orderBy('id')->get() : collect();
        if ($challans->isEmpty()) {
            $this->command?->warn('[ChallanItemSeeder] No challans present — run `ChallanSeeder` first.');
            return;
        }
        $grs = Schema::hasTable('grs') ? DB::table('grs')->orderBy('id')->get() : collect();
        if ($grs->isEmpty()) {
            $this->command?->warn('[ChallanItemSeeder] No GRs present — run `GrSeeder` first.');
            return;
        }

        $userIds = Schema::hasTable('users') ? DB::table('users')->pluck('id')->all() : [];
        $now = now();
        $linesInserted = 0;

        // Per-challan running total — we update challans.challan_total
        // at the end so the parent matches the sum of its lines.
        $perChallanTotal = [];

        foreach ($challans as $challan) {
            $lineCount = random_int(self::LINES_PER_CHALLAN_MIN, self::LINES_PER_CHALLAN_MAX);
            $seen = [];
            for ($j = 0; $j < $lineCount; $j++) {
                $gr = $grs->random();
                if (isset($seen[$gr->id])) {
                    continue; // a GR appears at most once per challan
                }
                $seen[$gr->id] = true;

                $surCh = (float) $gr->sur_ch;
                $cR    = (float) $gr->c_r;
                $other = (float) $gr->other;
                $frieght = (float) $gr->frieght_amount;
                $paid  = $gr->paid  ? (float) $gr->total_amount : null;
                $toPay = $gr->to_pay ? (float) $gr->total_amount : null;

                $payload = [
                    'gr_no'      => $gr->gr_no,
                    'challan_no' => $challan->challan_no,
                    'nugs'       => (int) $gr->nugs,
                    'meth'       => $gr->meth,
                    'description'=> $gr->description,
                    'weight'     => (float) $gr->weight,
                    'paid'       => $paid,
                    'to_pay'     => $toPay,
                    'sur_ch'     => $surCh,
                    'c_r'        => $cR,
                    'other'      => $other,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (\Schema::hasColumn('challan_iteams', 'gr_id')) {
                    $payload['gr_id'] = $gr->id;
                }
                if (\Schema::hasColumn('challan_iteams', 'challan_id')) {
                    $payload['challan_id'] = $challan->id;
                }
                if (\Schema::hasColumn('challan_iteams', 'created_by_id') && $userIds) {
                    $payload['created_by_id'] = $userIds[array_rand($userIds)];
                }

                // Match key — modernized FKs if both columns exist, else
                // the legacy string pair.
                if (\Schema::hasColumn('challan_iteams', 'challan_id') && \Schema::hasColumn('challan_iteams', 'gr_id')) {
                    DB::table('challan_iteams')->updateOrInsert(
                        ['challan_id' => $challan->id, 'gr_id' => $gr->id],
                        $payload,
                    );
                } else {
                    DB::table('challan_iteams')->updateOrInsert(
                        ['challan_no' => $challan->challan_no, 'gr_no' => $gr->gr_no],
                        $payload,
                    );
                }

                // Running total — the challan total is the sum of all line frieghts.
                $perChallanTotal[$challan->id] = ($perChallanTotal[$challan->id] ?? 0) + $frieght;
                $linesInserted++;
            }
        }

        // Recompute challans.challan_total from the sum of the lines.
        if (\Schema::hasColumn('challans', 'challan_total')) {
            foreach ($perChallanTotal as $challanId => $total) {
                DB::table('challans')->where('id', $challanId)->update([
                    'challan_total' => round($total, 2),
                ]);
            }
        }

        $this->command?->info(sprintf('[ChallanItemSeeder] %d lines seeded across %d challans.', $linesInserted, $challans->count()));
    }
}
