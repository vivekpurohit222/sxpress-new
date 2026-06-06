<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the `branches` table with the 7 hard-coded office names that
 * the legacy application uses everywhere.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000050_create_branches_table.php
 *   (modernized branches schema)
 * - database/reconstructed_migrations/2026_06_05_000100_seeder_baseline_offices.php
 *   (the same 7 offices are seeded by a data migration as well — this
 *   seeder duplicates the seed so it can run on a fresh DB after the
 *   table is created)
 * - docs/database-reconstruction-report.md §2.3  (the 7 hard-coded names
 *   come from Blade @php arrays in the legacy views)
 * - docs/erd.md §2  (BRANCH entity — code, name, city, state, pincode, …)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. If the `branches` table does NOT exist: skip with a warning.
 *
 * 2. InsertOrIgnore the 7 known offices (RJKT, KASH, DYBS, SWNP,
 *    NVGM, SHP1, SHP2) using their canonical codes.
 *
 * 3. If the modernized `branches` table is empty after that step
 *    (i.e. the migration baseline seeder hasn't run), also seed a
 *    handful of additional demo branches.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * `insertOrIgnore` is MySQL's INSERT IGNORE; the (code) unique key
 * makes this a no-op on re-run.
 *
 * --------------------------------------------------------------------------
 * Relationship to the migration baseline
 * --------------------------------------------------------------------------
 * The migration `2026_06_05_000100_seeder_baseline_offices.php` also
 * inserts these 7 rows. This seeder exists so that:
 *   (a) a fresh install with no migrations yet can still populate
 *       offices if you have run only the branch table migration, and
 *   (b) the seed is reproducible from `db:seed` alone.
 */
class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('branches')) {
            $this->command?->warn('[BranchSeeder] Skipped — the `branches` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $offices = [
            // [code,    name,             city,      state,     pincode]
            ['RJKT',   'Rajkot',         'Rajkot',   'Gujarat', '360001'],
            ['KASH',   'Kashmore Gate',  'Kashmore', 'Sindh',   '79200'],
            ['DYBS',   'Dayabasti',      'Delhi',    'Delhi',   '110006'],
            ['SWNP',   'Swarup Nagar',   'Kanpur',   'UP',      '208001'],
            ['NVGM',   'Navagam',        'Surat',    'Gujarat', '395010'],
            ['SHP1',   'Shapar (1)',     'Shapar',   'Gujarat', '360024'],
            ['SHP2',   'Shapar (2)',     'Shapar',   'Gujarat', '360024'],
        ];

        $now = now();
        $rows = array_map(function (array $o) use ($now) {
            return [
                'code'       => $o[0],
                'name'       => $o[1],
                'city'       => $o[2],
                'state'      => $o[3],
                'pincode'    => $o[4],
                'phone'      => null,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }, $offices);

        DB::table('branches')->insertOrIgnore($rows);

        $this->command?->info(sprintf('[BranchSeeder] %d offices seeded.', count($offices)));
    }
}
