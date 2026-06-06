<?php

namespace Database\Seeders;

use App\Models\challan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the legacy `challans` table with realistic demo data.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_12_09_090031_create_challans_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000021_add_status_columns.php
 * - database/reconstructed_migrations/2026_06_05_000010_add_fk_columns_and_drop_wrong_uniques.php
 *   (adds the integer `id` PK + `truck_id` FK)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 80 demo challans, each with:
 *      - unique challan_no (CH-0001 .. CH-0080)
 *      - from_dest, to_dest (one of the 7 offices)
 *      - challan_date (last 365 days)
 *      - truck_no, driver_name, license, owner_name (denormalized snapshot)
 *      - challan_total (sum of all its challan_iteams.frieght_amount)
 *
 * 2. Wires `truck_id` (modernized FK) if the `trucks` table exists.
 *
 * 3. The matching `challan_iteams` are inserted in
 *    `ChallanItemSeeder` (which derives its gr_id values from the
 *    real `grs` rows).
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `challan_no`. Re-running the seeder updates existing
 * rows but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed.
 */
class ChallanSeeder extends Seeder
{
    /**
     * Number of challans to create.
     */
    private const SEED_COUNT = 80;

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
        if (! Schema::hasTable('challans')) {
            $this->command?->warn('[ChallanSeeder] Skipped — the `challans` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('challans')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[ChallanSeeder] Skipped — %d challans already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        $trucks = Schema::hasTable('trucks') ? DB::table('trucks')->orderBy('id')->get() : collect();
        $userIds = Schema::hasTable('users') ? DB::table('users')->pluck('id')->all() : [];

        $now = now();
        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $challanNo = sprintf('CH-%04d', $i + 1);
            $from = self::OFFICES[$i % count(self::OFFICES)];
            $to   = self::OFFICES[($i + 2) % count(self::OFFICES)];
            $truck = $trucks->isNotEmpty() ? $trucks->random() : null;
            $challanTotal = $this->randomFloat(5000, 200000);

            $payload = [
                'challan_no'    => $challanNo,
                'from_dest'     => $from,
                'challan_date'  => $this->randomDate('-1 year', 'now'),
                'to_dest'       => $to,
                'truck_no'      => $truck->truck_no ?? $this->randomTruckNo(),
                'driver_name'   => $this->randomName(),
                'license'       => $this->randomLicense(),
                'owner_name'    => $this->randomOwnerName(),
                'note'          => $i % 9 === 0 ? 'Driver instructed to deliver before EOD' : null,
                'challan_total' => $challanTotal,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];

            if (\Schema::hasColumn('challans', 'truck_id') && $truck) {
                $payload['truck_id'] = $truck->id;
            }
            if (\Schema::hasColumn('challans', 'status')) {
                $payload['status'] = $this->randomElement(['in_transit', 'in_transit', 'delivered', 'closed', 'booked']);
            }
            if (\Schema::hasColumn('challans', 'created_by_id') && $userIds) {
                $payload['created_by_id'] = $userIds[array_rand($userIds)];
            }

            DB::table('challans')->updateOrInsert(
                ['challan_no' => $challanNo],
                $payload,
            );
        }

        $this->command?->info(sprintf('[ChallanSeeder] %d challans seeded.', self::SEED_COUNT));
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

    private function randomName(): string
    {
        $first = ['Rajesh', 'Suresh', 'Mahesh', 'Dinesh', 'Hitesh', 'Prakash', 'Rakesh', 'Mukesh', 'Jignesh', 'Kamlesh'];
        $last  = ['Patel', 'Shah', 'Joshi', 'Verma', 'Kumar', 'Singh', 'Parmar', 'Rathore', 'Chauhan', 'Yadav'];
        return $first[array_rand($first)].' '.$last[array_rand($last)];
    }

    private function randomLicense(): string
    {
        $state = ['GJ', 'MH', 'RJ', 'UP', 'DL', 'KA'][array_rand(['GJ', 'MH', 'RJ', 'UP', 'DL', 'KA'])];
        $rto   = str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
        $year  = (string) random_int(2010, (int) date('Y'));
        $serial= str_pad((string) random_int(1, 9999999), 7, '0', STR_PAD_LEFT);
        return "$state-$rto-$year-$serial";
    }

    private function randomOwnerName(): string
    {
        $surnames = ['Sharma', 'Patel', 'Singh', 'Kumar', 'Yadav', 'Verma', 'Parmar', 'Joshi', 'Rathore', 'Chauhan'];
        $prefixes = ['Shree', 'Sai', 'Balaji', 'Jai', 'Shiv', 'Ganesh', 'Ram', 'Krishna', 'New', 'Modern'];
        $suffixes = ['Transport', 'Trucking', 'Carriers', 'Logistics', 'Roadways', 'Motors', 'Travels', 'Freight'];
        return $prefixes[array_rand($prefixes)].' '.$surnames[array_rand($surnames)].' '.$suffixes[array_rand($suffixes)];
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
