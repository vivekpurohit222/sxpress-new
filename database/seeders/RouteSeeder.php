<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed (or skip) the **non-existent** `routes` table.
 *
 * --------------------------------------------------------------------------
 * Status
 * --------------------------------------------------------------------------
 * There is no `routes` table in:
 *   - database/migrations/*
 *   - database/reconstructed_migrations/*
 *   - docs/erd.md §2  (modernized ERD)
 *
 * The user's seed request listed "Routes" — this seeder exists so
 * the seeder chain compiles and can be wired into `DatabaseSeeder`,
 * but the seeder logs a warning and exits early when the table is
 * not present.
 *
 * --------------------------------------------------------------------------
 * When the table is added
 * --------------------------------------------------------------------------
 * The seeder will detect it and proceed to insert a small set of
 * plausible transport routes (e.g. Rajkot → Dayabasti Express).
 * The `RouteFactory` is the single source of data shape.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `code` (the suggested UNIQUE column for the not-yet-
 * existing `routes` table). Re-running is a no-op.
 */
class RouteSeeder extends Seeder
{
    /**
     * The seed set. We avoid the factory for these because we want
     * the (origin, destination, name) triples to be deterministic.
     *
     * @var array<int, array{0:string,1:string,2:string,3:float,4:int}>
     */
    private const ROUTES = [
        // [origin_code, destination_code, name, distance_km, transit_hours]
        ['RJKT', 'KASH', 'Rajkot — Kashmore Direct',          1450.50, 32],
        ['RJKT', 'DYBS', 'Rajkot — Dayabasti Express',         1150.00, 24],
        ['RJKT', 'NVGM', 'Rajkot — Navagam Local',              180.00,  6],
        ['RJKT', 'SHP1', 'Rajkot — Shapar (1) Shuttle',         22.00,  1],
        ['RJKT', 'SHP2', 'Rajkot — Shapar (2) Shuttle',         24.00,  1],
        ['NVGM', 'SHP1', 'Navagam — Shapar (1) Industrial',     210.00,  7],
        ['NVGM', 'SHP2', 'Navagam — Shapar (2) Industrial',     212.00,  7],
        ['SHP1', 'SHP2', 'Shapar (1) — Shapar (2) Connector',     4.00,  1],
        ['SWNP', 'DYBS', 'Swarup Nagar — Dayabasti Long Haul',  450.00, 12],
        ['KASH', 'DYBS', 'Kashmore — Dayabasti Premium',      2200.00, 44],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('routes')) {
            $this->command?->warn('[RouteSeeder] Skipped — the `routes` table does not exist. See docs/seeder-report.md §4 for the suggested schema and the rationale.');
            return;
        }

        // Resolve branches if available.
        $branchIds = DB::table('branches')->pluck('id', 'code')->all();

        $now = now();
        foreach (self::ROUTES as [$origin, $destination, $name, $distance, $transit]) {
            $code = strtoupper($origin).'-'.strtoupper($destination);
            $payload = [
                'code'      => $code,
                'name'      => $name,
                'distance_km'  => $distance,
                'transit_hours'=> $transit,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            // If origin_branch_id / destination_branch_id columns
            // exist (per the suggested future schema), wire them up.
            if (Schema::hasColumn('routes', 'origin_branch_id')) {
                $payload['origin_branch_id'] = $branchIds[$origin] ?? null;
            }
            if (Schema::hasColumn('routes', 'destination_branch_id')) {
                $payload['destination_branch_id'] = $branchIds[$destination] ?? null;
            }
            DB::table('routes')->updateOrInsert(['code' => $code], $payload);
        }

        $this->command?->info(sprintf('[RouteSeeder] %d routes seeded (requires `routes` table to exist).', count(self::ROUTES)));
    }
}
