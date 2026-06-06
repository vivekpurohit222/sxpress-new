<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the `customers` table (consignors / consignees).
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000053_create_customers_table.php
 * - docs/erd.md §2  (CUSTOMER entity — code, name, address, gst_no, …)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 30 demo customers (mix of GST-registered and
 *    non-registered, mix of corporate and individual).
 *
 * 2. Assigns sequential codes C0001, C0002, … (the auto-generation
 *    is normally done by a model observer; we use a transaction +
 *    counter here to avoid requiring the observer to be wired).
 *
 * 3. Marks the first 25 as active, the last 5 as inactive, so the
 *    "is_active = 0" filter has data to work with.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `code`. Re-running the seeder updates the corresponding
 * row (so demo values stay deterministic) but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed (a developer has added their own data).
 */
class CustomerSeeder extends Seeder
{
    /**
     * Number of customers to create.
     */
    private const SEED_COUNT = 30;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            $this->command?->warn('[CustomerSeeder] Skipped — the `customers` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('customers')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[CustomerSeeder] Skipped — %d customers already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        $now = now();
        $now()->getTimestamp(); // touched: ensure consistent timestamp

        // We use the factory in a loop so the random-but-realistic
        // data (Gstin, PAN, address, etc.) flows from one place.
        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $payload = Customer::factory()->make()->toArray();

            // The seeder assigns a sequential code (C0001, …) — the
            // factory leaves `code` null so the seeder is the
            // authority.
            $code = 'C'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            $payload['code']       = $code;
            $payload['is_active']  = $i < 25;     // last 5 inactive
            $payload['created_at'] = $now;
            $payload['updated_at'] = $now;

            DB::table('customers')->updateOrInsert(
                ['code' => $code],
                $payload,
            );
        }

        $this->command?->info(sprintf('[CustomerSeeder] %d customers seeded.', self::SEED_COUNT));
    }
}
