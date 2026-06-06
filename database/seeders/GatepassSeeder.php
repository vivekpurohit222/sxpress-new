<?php

namespace Database\Seeders;

use App\Models\gatepass;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the legacy `gatepasses` table with realistic demo data.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_10_29_082717_create_gatepasses_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000021_add_status_columns.php
 * - database/reconstructed_migrations/2026_06_05_000040_rename_legacy_columns.php
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 100 demo gatepasses, each derived from a real GR row.
 *    - We pick GRs at random and copy from_dest / to_dest / weight /
 *      nugs / frieght_amount from the GR.
 *    - gp_no is auto-assigned sequentially (1..100).
 *    - gr_id (modernized FK) is set if the column exists.
 *
 * 2. Adds the gp-specific charges (labour, dc) as fresh values
 *    derived from the freight.
 *
 * 3. Tries to wire `created_by_id` (audit column, modernized) if
 *    `users` exists.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `gp_no`. Re-running the seeder updates existing rows
 * but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed.
 */
class GatepassSeeder extends Seeder
{
    /**
     * Number of gatepasses to create.
     */
    private const SEED_COUNT = 100;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('gatepasses')) {
            $this->command?->warn('[GatepassSeeder] Skipped — the `gatepasses` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('gatepasses')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[GatepassSeeder] Skipped — %d gatepasses already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        // Pull GRs — the gatepasses derive from them.
        $grs = Schema::hasTable('grs') ? DB::table('grs')->orderBy('id')->get() : collect();
        if ($grs->isEmpty()) {
            $this->command?->warn('[GatepassSeeder] No GRs available — running GrSeeder first would be ideal.');
        }

        $userIds = Schema::hasTable('users') ? DB::table('users')->pluck('id')->all() : [];
        $now = now();

        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $gpNo = $i + 1;
            $gr   = $grs->isNotEmpty() ? $grs->random() : null;

            $frieght = $gr ? (float) $gr->frieght_amount : $this->randomFloat(500, 25000);
            $labour  = round($frieght * 0.04, 2);
            $other   = round($frieght * 0.02, 2);
            $dc      = $this->randomFloat(0, 500);
            $total   = round($frieght + $labour + $other + $dc, 2);

            $payload = [
                'gp_no'           => $gpNo,
                'gp_date'         => $this->randomDate('-1 year', 'now'),
                'from_dest'       => $gr->from_dest ?? 'Rajkot',
                'to_dest'         => $gr->to_dest   ?? 'Shapar (1)',
                'gr_no'           => $gr->gr_no     ?? sprintf('??-%05d', $i + 1),
                'weight'          => $gr->weight    ?? $this->randomFloat(50, 5000, 3),
                'nugs'            => $gr->nugs      ?? $this->randomInt(1, 200),
                'pm'              => $this->randomElement(['Paid', 'To Pay', 'TBB', 'FOC']),
                'frieght_amount'  => $frieght,
                'labour_amount'   => $labour,
                'other'           => $other,
                'total_amount'    => $total,
                'note'            => $i % 7 === 0 ? 'Special handling required' : null,
                'created_at'      => $now,
                'updated_at'      => $now,
            ];

            // consignor / m_s — modernized name preferred, legacy fallback.
            $consignor = $gr->consignor ?? 'Demo Consignor '.$i;
            if (\Schema::hasColumn('gatepasses', 'consignor')) {
                $payload['consignor'] = $consignor;
            } else {
                $payload['m_s'] = $consignor;
            }

            // delivery_charge / dc_amount — modernized name preferred.
            if (\Schema::hasColumn('gatepasses', 'delivery_charge')) {
                $payload['delivery_charge'] = $dc;
            } else {
                $payload['dc_amount'] = $dc;
            }

            // gr_id (modernized FK).
            if (\Schema::hasColumn('gatepasses', 'gr_id') && $gr) {
                $payload['gr_id'] = $gr->id;
            }

            if (\Schema::hasColumn('gatepasses', 'status')) {
                $payload['status'] = $this->randomElement(['in_transit', 'in_transit', 'delivered', 'closed', 'booked']);
            }

            if (\Schema::hasColumn('gatepasses', 'created_by_id') && $userIds) {
                $payload['created_by_id'] = $userIds[array_rand($userIds)];
            }

            DB::table('gatepasses')->updateOrInsert(
                ['gp_no' => $gpNo],
                $payload,
            );
        }

        $this->command?->info(sprintf('[GatepassSeeder] %d gatepasses seeded.', self::SEED_COUNT));
    }

    private function randomFloat(float $min, float $max, int $decimals = 2): float
    {
        $factor = 10 ** $decimals;
        return round(random_int((int)($min * $factor), (int)($max * $factor)) / $factor, $decimals);
    }

    private function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    private function randomDate(string $start, string $end): string
    {
        $ts = random_int(strtotime($start), strtotime($end));
        return date('Y-m-d', $ts);
    }

    private function randomElement(array $pool)
    {
        return $pool[array_rand($pool)];
    }
}
