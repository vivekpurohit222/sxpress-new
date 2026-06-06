<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * The end-to-end demo seeder for the modernized Saurashtra Express schema.
 *
 * --------------------------------------------------------------------------
 * Purpose
 * --------------------------------------------------------------------------
 * `DatabaseSeeder` is left untouched per the task brief. This seeder is
 * the orchestrator that runs the ten sub-seeders in the order required
 * to satisfy foreign-key dependencies:
 *
 *     1. BranchSeeder        — 7 offices (no dependencies)
 *     2. RolePermissionSeeder — 4 roles + canonical permission set
 *     3. UserSeeder          — 1 super-admin, 7 branch managers, 5 operators
 *     4. CustomerSeeder      — 30 demo customers (consignor/consignee master)
 *     5. VendorSeeder        — 25 demo vendors (truck owners)
 *     6. VehicleSeeder       — 40 demo trucks (FK → vendors, branches)
 *     7. DriverSeeder        — 50 demo drivers (independent of vehicles)
 *     8. RouteSeeder         — 10 demo routes (skipped — no `routes` table)
 *     9. StationSeeder       — 8 demo stations (skipped — no `stations` table)
 *
 *   The two skipped seeders (Route, Station) ship with guard clauses that
 *   log a warning and exit cleanly. They will start seeding the moment a
 *   `routes` or `stations` migration is added.
 *
 * --------------------------------------------------------------------------
 * How to run
 * --------------------------------------------------------------------------
 *     # from a clean modernized DB (all migrations applied):
 *     php artisan db:seed --class=Database\\Seeders\\DemoSeeder
 *
 *     # or, from a fresh DB (runs migrations then seeds):
 *     php artisan migrate:fresh --seed --class=Database\\Seeders\\DemoSeeder
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Every child seeder is `updateOrInsert` / `firstOrCreate` based, so this
 * dispatcher is safe to re-run. Existing demo rows are refreshed in
 * place; the `existing >= SEED_COUNT` guards in the child seeders turn
 * the dispatch into a no-op once the demo set is in place.
 *
 * --------------------------------------------------------------------------
 * Dependencies (tables)
 * --------------------------------------------------------------------------
 *   - `branches`            → seeded by BranchSeeder (no FKs)
 *   - `roles`/`permissions` → seeded by RolePermissionSeeder (no FKs)
 *   - `users`               → references `branches.id` (modernized) and
 *                              `roles.name` (Spatie)
 *   - `customers`           → no FKs
 *   - `vendors`             → no FKs
 *   - `trucks`              → references `vendors.id`, `branches.id`
 *   - `drivers`             → no FKs (the `truck_assignments` pivot is
 *                              populated separately, if at all)
 *
 *   The two skipped seeders reference `branches.id` (RouteSeeder via
 *   origin_branch_id, StationSeeder via branch_id) but their table
 *   guards prevent execution on a missing table.
 */
class DemoSeeder extends Seeder
{
    /**
     * The ordered list of child seeders to invoke.
     *
     * Order is critical: a child must run only after every table it
     * references has been populated.
     *
     * @var array<int, class-string<Seeder>>
     */
    private const SEEDERS = [
        BranchSeeder::class,
        RolePermissionSeeder::class,
        UserSeeder::class,
        CustomerSeeder::class,
        VendorSeeder::class,
        VehicleSeeder::class,
        DriverSeeder::class,
        RouteSeeder::class,
        StationSeeder::class,
    ];

    /**
     * Run the demo seed set.
     *
     * @return void
     */
    public function run(): void
    {
        $this->command?->info('[DemoSeeder] Starting demo seed set (10 seeders in dependency order).');

        foreach (self::SEEDERS as $seederClass) {
            $shortName = class_basename($seederClass);

            // Each child is responsible for its own `Schema::hasTable`
            // guard, so the dispatcher only needs to wrap in try/catch
            // to keep the chain moving if a single seeder fails on a
            // legacy DB that is missing a modernized table.
            try {
                $this->call($seederClass);
            } catch (\Throwable $e) {
                $this->command?->error(sprintf(
                    '[DemoSeeder] %s failed: %s',
                    $shortName,
                    $e->getMessage(),
                ));
                // Continue with the rest of the chain.
            }
        }

        $this->command?->info('[DemoSeeder] Done. See docs/seeder-report.md for the row counts and any skip notices.');
    }
}
