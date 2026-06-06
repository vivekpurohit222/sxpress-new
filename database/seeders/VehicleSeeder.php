<?php

namespace Database\Seeders;

use App\Models\Truck;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the modernized `trucks` (a.k.a. "vehicles") table.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000080_create_trucks_table.php
 * - docs/erd.md §2  (TRUCK entity — truck_no, owner_vendor_id, …)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 40 demo vehicles, each with a real-looking Indian
 *    truck number, plausible make/model/year, and active
 *    fitness/insurance/permit expiries.
 *
 * 2. Randomly assigns each truck to one of the seeded vendors
 *    (V0001..V0020) and to one of the seeded branches.
 *
 * 3. 2 trucks get an EXPIRED permit (compliance flag, see
 *    VehicleFactory::expiredPermit()).
 *
 * 4. 1 truck is marked inactive.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `truck_no`. Re-running the seeder updates existing
 * rows but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed.
 *
 * --------------------------------------------------------------------------
 * Why 40?
 * --------------------------------------------------------------------------
 * The legacy `truckdrivers` table is the source of truth in the
 * existing application code. We seed 40 modernized trucks so that
 * the new code paths (truck → owner_vendor → branch) have enough
 * data to render meaningful reports.
 */
class VehicleSeeder extends Seeder
{
    /**
     * Number of vehicles to create.
     */
    private const SEED_COUNT = 40;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('trucks')) {
            $this->command?->warn('[VehicleSeeder] Skipped — the `trucks` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('trucks')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[VehicleSeeder] Skipped — %d trucks already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        // Resolve vendor + branch lookups once.
        $vendorIds = Schema::hasTable('vendors')
            ? DB::table('vendors')->orderBy('id')->pluck('id')->all()
            : [];
        $branchIds = Schema::hasTable('branches')
            ? DB::table('branches')->orderBy('id')->pluck('id')->all()
            : [];

        $now = now();
        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $factory = Truck::factory();
            if ($i >= 38) {
                $factory = $factory->expiredPermit();
            }
            if ($i === 39) {
                $factory = $factory->state(fn () => ['is_active' => false]);
            }
            $payload = $factory->make()->toArray();

            // Assign a random owner vendor and home branch.
            $payload['owner_vendor_id'] = $vendorIds ? $vendorIds[array_rand($vendorIds)] : null;
            $payload['home_branch_id']  = $branchIds ? $branchIds[array_rand($branchIds)] : null;
            $payload['created_at']      = $now;
            $payload['updated_at']      = $now;

            DB::table('trucks')->updateOrInsert(
                ['truck_no' => $payload['truck_no']],
                $payload,
            );
        }

        $this->command?->info(sprintf('[VehicleSeeder] %d vehicles seeded.', self::SEED_COUNT));
    }
}
