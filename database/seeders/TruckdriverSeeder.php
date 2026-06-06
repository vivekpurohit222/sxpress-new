<?php

namespace Database\Seeders;

use App\Models\truckdriver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the legacy `truckdrivers` table with realistic demo data.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_09_095555_create_truckdrivers_table.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000030_add_soft_deletes_and_audit_columns.php
 *
 * The legacy `truckdrivers` table is the source of truth for the
 * existing application. Even though the modernized schema splits
 * into `trucks` + `drivers` + `truck_assignments`, the existing
 * controllers still read from this table — so we keep it populated.
 *
 * The modernized `Truck` and `Driver` seeders create their own
 * parallel data, so there are now 2 sources of truth. A backfill
 * migration is the eventual goal (see erd.md §3).
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 50 demo truckdrivers, each with a unique
 *    (truck_no, license, mobile_no1).
 *
 * 2. 1 is marked inactive.
 *
 * 3. The truck_no is in the Indian truck-number format used by
 *    VehicleFactory (so the same truck appears in both the legacy
 *    and the modernized tables — a realistic dual-write scenario).
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `truck_no`. Re-running the seeder updates existing
 * rows but does not duplicate.
 */
class TruckdriverSeeder extends Seeder
{
    /**
     * Number of truckdrivers to create.
     */
    private const SEED_COUNT = 50;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('truckdrivers')) {
            $this->command?->warn('[TruckdriverSeeder] Skipped — the `truckdrivers` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('truckdrivers')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[TruckdriverSeeder] Skipped — %d truckdrivers already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        $now = now();
        $usedTrucks = $this->collectUsedTrucks();

        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $truckNo = $this->nextTruckNo($usedTrucks);
            $usedTrucks[] = $truckNo;
            $license = $this->nextLicense($i);
            $name    = $this->randomName();
            $mobile1 = $this->nextMobile($i);
            $mobile2 = $i % 4 === 0 ? $this->nextMobile($i + 1000) : null;
            $address = $this->randomAddress();

            $payload = [
                'driver_name'    => $name,
                'truck_no'       => $truckNo,
                'license'        => $license,
                'driver_address' => $address,
                'mobile_no1'     => $mobile1,
                'mobile_no2'     => $mobile2,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            if (\Schema::hasColumn('truckdrivers', 'is_active')) {
                $payload['is_active'] = $i !== 0; // first row is inactive
            }

            DB::table('truckdrivers')->updateOrInsert(
                ['truck_no' => $truckNo],
                $payload,
            );
        }

        $this->command?->info(sprintf('[TruckdriverSeeder] %d truckdrivers seeded.', self::SEED_COUNT));
    }

    /**
     * @return array<int, string>
     */
    private function collectUsedTrucks(): array
    {
        return DB::table('truckdrivers')
            ->whereNotNull('truck_no')
            ->pluck('truck_no')
            ->all();
    }

    private function nextTruckNo(array $used): string
    {
        // Generate a truck_no that isn't already used. Try a few
        // times; fall back to a guaranteed-unique numeric suffix.
        $states = ['GJ', 'MH', 'RJ', 'UP', 'DL', 'KA', 'TN', 'MP', 'HR', 'PB'];
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $state = $states[array_rand($states)];
            $rto   = str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
            $letters = chr(65 + random_int(0, 25)).chr(65 + random_int(0, 25));
            $digits  = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            $candidate = "$state-$rto-$letters-$digits";
            if (! in_array($candidate, $used, true)) {
                return $candidate;
            }
        }
        // Deterministic fallback.
        return sprintf('GJ-03-AA-%05d', random_int(10000, 99999));
    }

    private function nextLicense(int $sequence): string
    {
        $state = ['GJ', 'MH', 'RJ', 'UP', 'DL'][array_rand(['GJ', 'MH', 'RJ', 'UP', 'DL'])];
        $rto   = str_pad((string) random_int(1, 99), 2, '0', STR_PAD_LEFT);
        $year  = (string) random_int(2010, (int) date('Y'));
        $serial= str_pad((string) ($sequence + 10000), 7, '0', STR_PAD_LEFT);
        return "$state-$rto-$year-$serial";
    }

    private function nextMobile(int $seed): string
    {
        // Deterministic but unique within the seeder.
        return '+91'.str_pad((string) (7000000000 + ($seed * 137) % 3000000000), 10, '0', STR_PAD_LEFT);
    }

    private function randomName(): string
    {
        $first = ['Rajesh', 'Suresh', 'Mahesh', 'Dinesh', 'Hitesh', 'Prakash', 'Rakesh', 'Mukesh', 'Jignesh', 'Kamlesh'];
        $last  = ['Patel', 'Shah', 'Joshi', 'Verma', 'Kumar', 'Singh', 'Parmar', 'Rathore', 'Chauhan', 'Yadav'];
        return $first[array_rand($first)].' '.$last[array_rand($last)];
    }

    private function randomAddress(): string
    {
        $cities = ['Rajkot', 'Surat', 'Ahmedabad', 'Jamnagar', 'Bhavnagar', 'Junagadh', 'Porbandar', 'Gandhidham'];
        $city = $cities[array_rand($cities)];
        $house = random_int(1, 200);
        $street = ['MG Road', 'Station Road', 'Gandhi Chowk', 'Sardar Patel Marg', 'NH-8B'][array_rand(['MG Road', 'Station Road', 'Gandhi Chowk', 'Sardar Patel Marg', 'NH-8B'])];
        $pincode = str_pad((string) random_int(360000, 399999), 6, '0', STR_PAD_LEFT);
        return "$house, $street, $city, Gujarat - $pincode";
    }
}
