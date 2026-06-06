<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed (or skip) the **non-existent** `stations` table.
 *
 * --------------------------------------------------------------------------
 * Status
 * --------------------------------------------------------------------------
 * There is no `stations` table in:
 *   - database/migrations/*
 *   - database/reconstructed_migrations/*
 *   - docs/erd.md §2  (modernized ERD)
 *
 * The user's seed request listed "Stations" — this seeder exists so
 * the seeder chain compiles and can be wired into `DatabaseSeeder`,
 * but the seeder logs a warning and exits early when the table is
 * not present.
 *
 * In the current codebase, "stations" (if interpreted as physical
 * pickup/drop-off points) is closest to the `branches` table — see
 * `BranchSeeder` for the actual office list. This seeder is for the
 * "logistics touch-point" master if/when it is added.
 *
 * --------------------------------------------------------------------------
 * When the table is added
 * --------------------------------------------------------------------------
 * The seeder will detect it and proceed to insert a small set of
 * plausible stations. The `StationFactory` is the single source of
 * data shape.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `code` (the suggested UNIQUE column for the not-yet-
 * existing `stations` table). Re-running is a no-op.
 */
class StationSeeder extends Seeder
{
    /**
     * The seed set. We avoid the factory for these because we want
     * the (name, branch, coordinates) triples to be deterministic.
     *
     * @var array<int, array{0:string,1:string,2:string,3:float,4:float}>
     */
    private const STATIONS = [
        // [code, name, branch_code, latitude, longitude]
        ['STN-RJKT-01', 'Rajkot Main Cross-Dock',       'RJKT', 22.3039, 70.8022],
        ['STN-RJKT-02', 'Rajkot Container Yard',        'RJKT', 22.2891, 70.7831],
        ['STN-KASH-01', 'Kashmore Gate Terminal',       'KASH', 27.9996, 69.3000],
        ['STN-DYBS-01', 'Dayabasti Freight Terminal',   'DYBS', 28.6850, 77.1030],
        ['STN-SWNP-01', 'Swarup Nagar Loading Bay',     'SWNP', 26.4499, 80.3319],
        ['STN-NVGM-01', 'Navagam Inland Container Depot','NVGM', 21.1702, 72.8311],
        ['STN-SHP1-01', 'Shapar (1) Bonded Warehouse',  'SHP1', 22.3456, 70.7890],
        ['STN-SHP2-01', 'Shapar (2) Truck Terminal',    'SHP2', 22.3500, 70.7935],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('stations')) {
            $this->command?->warn('[StationSeeder] Skipped — the `stations` table does not exist. See docs/seeder-report.md §4 for the suggested schema and the rationale.');
            return;
        }

        $branchIds = Schema::hasTable('branches')
            ? DB::table('branches')->pluck('id', 'code')->all()
            : [];

        $now = now();
        foreach (self::STATIONS as [$code, $name, $branchCode, $lat, $lng]) {
            $payload = [
                'code'      => $code,
                'name'      => $name,
                'latitude'  => $lat,
                'longitude' => $lng,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            if (Schema::hasColumn('stations', 'branch_id')) {
                $payload['branch_id'] = $branchIds[$branchCode] ?? null;
            }
            DB::table('stations')->updateOrInsert(['code' => $code], $payload);
        }

        $this->command?->info(sprintf('[StationSeeder] %d stations seeded (requires `stations` table to exist).', count(self::STATIONS)));
    }
}
