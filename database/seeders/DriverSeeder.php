<?php

namespace Database\Seeders;

use App\Models\Driver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the modernized `drivers` table.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000081_create_drivers_table.php
 * - docs/erd.md §2  (DRIVER entity — name, license, address, mobile, …)
 * - database/reconstructed_migrations/2026_06_05_000082_create_truck_assignments_table.php
 *   (pivot — created separately, this seeder does not write to it)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 50 demo drivers, each with a unique license number.
 *
 * 2. 2 drivers get an EXPIRED license (compliance flag).
 *
 * 3. 1 driver is marked inactive.
 *
 * 4. Drivers are NOT pre-assigned to a truck here. The modernized
 *    model uses a `truck_assignments` pivot (with `assigned_from` /
 *    `assigned_to` dates) so a driver can move trucks over time.
 *    A separate `TruckAssignmentSeeder` (if/when added) would
 *    create realistic assignment histories.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `license`. Re-running the seeder updates existing
 * rows but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed.
 */
class DriverSeeder extends Seeder
{
    /**
     * Number of drivers to create.
     */
    private const SEED_COUNT = 50;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('drivers')) {
            $this->command?->warn('[DriverSeeder] Skipped — the `drivers` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('drivers')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[DriverSeeder] Skipped — %d drivers already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        $now = now();
        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $factory = Driver::factory();
            if ($i >= 48) {
                $factory = $factory->expiredLicense();
            }
            if ($i === 49) {
                $factory = $factory->inactive();
            }
            $payload = $factory->make()->toArray();

            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;

            DB::table('drivers')->updateOrInsert(
                ['license' => $payload['license']],
                $payload,
            );
        }

        $this->command?->info(sprintf('[DriverSeeder] %d drivers seeded.', self::SEED_COUNT));
    }
}
