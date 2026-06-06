<?php

namespace Database\Seeders;

use App\Models\Vendor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the `vendors` table (truck owners).
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000054_create_vendors_table.php
 * - docs/erd.md §2  (VENDOR entity — code, name, address, gst_no, …)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 25 demo vendors (truck owners / transporters).
 *
 * 2. Assigns sequential codes V0001, V0002, …
 *
 * 3. The first 15 vendors have full bank settlement details
 *    (IFSC + account number) — these are the "preferred" partners
 *    that the application will surface first.
 *
 * 4. Marks the last 4 as inactive.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `code`. Re-running the seeder updates existing rows but
 * does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed (a developer has added their own data).
 */
class VendorSeeder extends Seeder
{
    /**
     * Number of vendors to create.
     */
    private const SEED_COUNT = 25;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('vendors')) {
            $this->command?->warn('[VendorSeeder] Skipped — the `vendors` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('vendors')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[VendorSeeder] Skipped — %d vendors already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        $now = now();

        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $useBank = $i < 15;
            $factory = Vendor::factory();
            if ($useBank) {
                $factory = $factory->withBankDetails();
            }
            $payload = $factory->make()->toArray();

            $code = 'V'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            $payload['code']       = $code;
            $payload['is_active']  = $i < 21;     // last 4 inactive
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;

            DB::table('vendors')->updateOrInsert(
                ['code' => $code],
                $payload,
            );
        }

        $this->command?->info(sprintf('[VendorSeeder] %d vendors seeded.', self::SEED_COUNT));
    }
}
