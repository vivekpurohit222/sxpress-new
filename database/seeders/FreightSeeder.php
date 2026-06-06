<?php

namespace Database\Seeders;

use App\Models\Freight;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the legacy `frieghts` (Freight Memos) table with realistic
 * demo data.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_08_120739_create_frieghts_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000040_rename_legacy_columns.php
 *   (renames `balance_to_sn` → `balance_due`)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 60 demo freight memos, each:
 *      - With a unique `fm_no` (FM-0001 .. FM-0060)
 *      - Pinned to a truck (modernized `truck_id` FK if the column
 *        exists; otherwise the legacy `truck_no` string)
 *      - With 2-4 entry line charges
 *      - With realistic commission / other_charges / extra / balance
 *
 * 2. Wires `created_by_id` (modernized audit column) if available.
 *
 * 3. Tries to source `truck_no` from the seeded `trucks` table to
 *    keep the denormalized string consistent with the FK.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `fm_no`. Re-running the seeder updates existing rows
 * but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed.
 */
class FreightSeeder extends Seeder
{
    /**
     * Number of freight memos to create.
     */
    private const SEED_COUNT = 60;

    /**
     * The 7 office names.
     */
    private const OFFICES = [
        'Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar',
        'Navagam', 'Shapar (1)', 'Shapar (2)',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('frieghts')) {
            $this->command?->warn('[FreightSeeder] Skipped — the `frieghts` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('frieghts')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[FreightSeeder] Skipped — %d freight memos already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        $trucks = Schema::hasTable('trucks') ? DB::table('trucks')->orderBy('id')->get() : collect();
        $userIds = Schema::hasTable('users') ? DB::table('users')->pluck('id')->all() : [];

        $now = now();
        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $fmNo = sprintf('FM-%04d', $i + 1);
            $truck = $trucks->isNotEmpty() ? $trucks->random() : null;

            $entry1 = $this->randomFloat(0, 5000);
            $entry2 = $this->randomFloat(0, 3000);
            $entry3 = $this->randomFloat(0, 2000);
            $entry4 = $this->randomFloat(0, 1000);
            $total  = round($entry1 + $entry2 + $entry3 + $entry4, 2);

            $truckFreight = $this->randomFloat(5000, 100000);
            $commission   = round($truckFreight * $this->randomFloat(0.05, 0.12), 2);
            $otherCharges = $this->randomFloat(0, 2000);
            $extra        = $this->randomFloat(0, 1500);
            $balance      = round($truckFreight - $commission - $otherCharges - $extra, 2);

            $payload = [
                'fm_no'          => $fmNo,
                'fm_date'        => $this->randomDate('-1 year', 'now'),
                'from_dest'      => self::OFFICES[$i % count(self::OFFICES)],
                'to_dest'        => self::OFFICES[($i + 1) % count(self::OFFICES)],
                'truck_no'       => $truck->truck_no ?? $this->randomTruckNo(),
                'entry_1'        => ['Loading charges', 'Unloading charges', 'Detention', 'Warai'][array_rand(['Loading charges', 'Unloading charges', 'Detention', 'Warai'])],
                'entry_1_amount' => $entry1,
                'entry_2'        => ['Toll', 'RTO', 'Weighbridge', 'Penalty'][array_rand(['Toll', 'RTO', 'Weighbridge', 'Penalty'])],
                'entry_2_amount' => $entry2,
                'entry_3'        => $i % 2 === 0 ? 'Mamul' : 'Dharmada',
                'entry_3_amount' => $entry3,
                'entry_4'        => $i % 3 === 0 ? 'Advance' : null,
                'entry_4_amount' => $i % 3 === 0 ? $entry4 : 0,
                'total_amount'   => $total,
                'truck_freight'  => $truckFreight,
                'commission'     => $commission,
                'other_charges'  => $otherCharges,
                'extra'          => $extra,
                'note'           => $i % 5 === 0 ? 'Settled against cash receipt' : null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            // balance_due / balance_to_sn — modernized name preferred.
            if (\Schema::hasColumn('frieghts', 'balance_due')) {
                $payload['balance_due'] = $balance;
            } else {
                $payload['balance_to_sn'] = $balance;
            }

            // truck_id (modernized FK).
            if (\Schema::hasColumn('frieghts', 'truck_id') && $truck) {
                $payload['truck_id'] = $truck->id;
            }
            if (\Schema::hasColumn('frieghts', 'created_by_id') && $userIds) {
                $payload['created_by_id'] = $userIds[array_rand($userIds)];
            }

            DB::table('frieghts')->updateOrInsert(
                ['fm_no' => $fmNo],
                $payload,
            );
        }

        $this->command?->info(sprintf('[FreightSeeder] %d freight memos seeded.', self::SEED_COUNT));
    }

    private function randomFloat(float $min, float $max, int $decimals = 2): float
    {
        $factor = 10 ** $decimals;
        return round(random_int((int)($min * $factor), (int)($max * $factor)) / $factor, $decimals);
    }

    private function randomTruckNo(): string
    {
        $states = ['GJ', 'MH', 'RJ', 'UP', 'DL', 'KA'];
        $state  = $states[array_rand($states)];
        $rto    = str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
        $letters= chr(65 + random_int(0, 25)).chr(65 + random_int(0, 25));
        $digits = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        return "$state-$rto-$letters-$digits";
    }

    private function randomDate(string $start, string $end): string
    {
        $ts = random_int(strtotime($start), strtotime($end));
        return date('Y-m-d', $ts);
    }
}
