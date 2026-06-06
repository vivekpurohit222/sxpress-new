<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seed the legacy `grs` (Goods Receipt) table with realistic demo data.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_22_053810_cretae_grs_table.php  (legacy)
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000021_add_status_columns.php
 * - database/reconstructed_migrations/2026_06_05_000040_rename_legacy_columns.php
 * - docs/database-reconstruction-report.md §4  (grs semantics)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 200 demo GRs with random:
 *      - gr_no         (unique — both forms)
 *      - from_dest     (one of the 7 offices)
 *      - to_dest       (one of the 7 offices, != from_dest)
 *      - copy_date     (last 365 days)
 *      - consignor + consignee (with address + GST)
 *      - weight, packages, freight, total
 *      - paid / to_pay mix
 *      - status (booked / in_transit / delivered / closed)
 *
 * 2. Distributes GRs roughly evenly across the 7 offices (matches
 *    the per-office GR series pattern documented in
 *    docs/erp-workflow-map.md §2).
 *
 * 3. Tries to wire `consignor_id` / `consignee_id` (modernized FKs)
 *    if `customers` exists and was populated by `CustomerSeeder`.
 *
 * 4. Tries to wire `created_by_id` (audit column, modernized) if
 *    `users` exists and was populated by `UserSeeder`.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Matches by `gr_no`. Re-running the seeder updates existing rows
 * but does not duplicate.
 *
 * If the table already has more rows than we are about to seed, we
 * skip the seed.
 */
class GrSeeder extends Seeder
{
    /**
     * Number of GRs to create.
     */
    private const SEED_COUNT = 200;

    /**
     * The 7 office names (legacy `from_dest` / `to_dest` vocabulary).
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
        if (! Schema::hasTable('grs')) {
            $this->command?->warn('[GrSeeder] Skipped — the `grs` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $existing = DB::table('grs')->count();
        if ($existing >= self::SEED_COUNT) {
            $this->command?->info(sprintf(
                '[GrSeeder] Skipped — %d GRs already present (>= %d).',
                $existing,
                self::SEED_COUNT,
            ));
            return;
        }

        // Optional: pull customer + user lookups for FK wiring.
        $customerIds = Schema::hasTable('customers')
            ? DB::table('customers')->pluck('id')->all()
            : [];
        $userIds = Schema::hasTable('users')
            ? DB::table('users')->pluck('id')->all()
            : [];

        $now = now();
        $consignorAddresses = $this->fakeAddresses();
        $consigneeAddresses = $this->fakeAddresses();

        for ($i = 0; $i < self::SEED_COUNT; $i++) {
            $from = self::OFFICES[$i % count(self::OFFICES)];
            $to   = self::OFFICES[($i + 1) % count(self::OFFICES)];

            $frieght = $this->fakerRandomFloat(500, 25000);
            $surCh   = $this->fakerRandomFloat(0, 500);
            $cR      = $this->fakerRandomFloat(0, 300);
            $other   = $this->fakerRandomFloat(0, 1500);
            $bc      = $this->fakerRandomFloat(0, 200);
            $total   = round($frieght + $surCh + $cR + $other + $bc, 2);

            $consignor = $this->fakerCompany();
            $consignee = $this->fakerCompany();
            $consignorGst = $this->fakerGstin();
            $consigneeGst = $this->fakerGstin();
            $grNo = $this->buildGrNo($from, $i);

            $payload = [
                'gr_no'             => $grNo,
                'from_dest'         => $from,
                'to_dest'           => $to,
                'copy_date'         => $this->fakerDateBetween('-1 year', 'now'),
                'consignor'         => $consignor,
                'consignee'         => $consignee,
                'nugs'              => $this->fakerNumberBetween(1, 200),
                'meth'              => ['C_R', 'C_B', 'Bags'][($i % 3)],
                'description'       => $this->fakerDescription(),
                'pm'                => ['Paid', 'To Pay', 'TBB', 'FOC'][$i % 4],
                'eway_bill_number'  => $i % 5 === 0 ? null : str_pad((string) (100000000000 + $i), 12, '0', STR_PAD_LEFT),
                'bill_amount'       => $this->fakerRandomFloat(1000, 200000),
                'weight'            => $this->fakerRandomFloat(50, 5000, 3),
                'paid'              => ($i % 2) === 0 ? 1 : 0,
                'to_pay'            => ($i % 2) === 0 ? 0 : 1,
                'frieght_amount'    => $frieght,
                'sur_ch'            => $surCh,
                'c_r'               => $cR,
                'other'             => $other,
                'bc_amount'         => $bc,
                'total_amount'      => $total,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            // Address + GST — modernized names preferred, legacy fallback.
            $consignorAddress = $consignorAddresses[$i % count($consignorAddresses)];
            $consigneeAddress = $consigneeAddresses[$i % count($consigneeAddresses)];
            if (\Schema::hasColumn('grs', 'consignor_address')) {
                $payload['consignor_address'] = $consignorAddress;
            } else {
                $payload['nor_adress'] = $consignorAddress;
            }
            if (\Schema::hasColumn('grs', 'consignee_address')) {
                $payload['consignee_address'] = $consigneeAddress;
            } else {
                $payload['nee_adress'] = $consigneeAddress;
            }
            if (\Schema::hasColumn('grs', 'consignor_gst_no')) {
                $payload['consignor_gst_no'] = $consignorGst;
            } else {
                $payload['nor_gst_no'] = $consignorGst;
            }
            if (\Schema::hasColumn('grs', 'consignee_gst_no')) {
                $payload['consignee_gst_no'] = $consigneeGst;
            } else {
                $payload['nee_gst_no'] = $consigneeGst;
            }

            if (\Schema::hasColumn('grs', 'status')) {
                // Distribute statuses: 50% booked, 25% in_transit,
                // 15% delivered, 10% closed.
                $statuses = ['booked', 'booked', 'in_transit', 'in_transit', 'delivered', 'closed', 'closed', 'delivered', 'booked', 'booked'];
                $payload['status'] = $statuses[$i % 10];
            }

            // Optional modernized FKs.
            if (\Schema::hasColumn('grs', 'consignor_id') && $customerIds) {
                $payload['consignor_id'] = $customerIds[array_rand($customerIds)];
            }
            if (\Schema::hasColumn('grs', 'consignee_id') && $customerIds) {
                $payload['consignee_id'] = $customerIds[array_rand($customerIds)];
            }
            if (\Schema::hasColumn('grs', 'created_by_id') && $userIds) {
                $payload['created_by_id'] = $userIds[array_rand($userIds)];
            }

            DB::table('grs')->updateOrInsert(
                ['gr_no' => $grNo],
                $payload,
            );
        }

        $this->command?->info(sprintf('[GrSeeder] %d GRs seeded.', self::SEED_COUNT));
    }

    /**
     * Build a per-office GR number, e.g. AA-00001, KA-00001, …
     *
     * @param  string  $office
     * @param  int     $sequence
     * @return string
     */
    private function buildGrNo(string $office, int $sequence): string
    {
        $prefix = match ($office) {
            'Rajkot'         => 'AA',
            'Kashmore Gate'  => 'KA',
            'Dayabasti'      => 'DB',
            'Swarup Nagar'   => 'SN',
            'Navagam'        => 'NV',
            'Shapar (1)'     => 'S1',
            'Shapar (2)'     => 'S2',
            default          => 'AA',
        };
        return $prefix.'-'.str_pad((string) (($sequence / 7) + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * @return array<int, string>
     */
    private function fakeAddresses(): array
    {
        return [
            'Plot 14, GIDC Industrial Estate, Vatva, Ahmedabad - 382445',
            '12/A, MIDC, Andheri East, Mumbai - 400093',
            'Sector 17, HUDA Market, Gurugram, Haryana - 122001',
            '88, Industrial Area, Ludhiana, Punjab - 141001',
            'A-101, Industrial Suburb, Coimbatore, Tamil Nadu - 641021',
            'Block D, MIDC, Pune, Maharashtra - 411019',
            'Plot 7, Sahakari Bhavan, Rajkot, Gujarat - 360002',
        ];
    }

    /**
     * @return string
     */
    private function fakerCompany(): string
    {
        static $pool = [
            'Shree Balaji Cotton Pvt Ltd', 'Sai Cement Corporation', 'Om Steel Industries',
            'Ganesh Textiles LLP', 'Patel Fertilisers Ltd', 'Hindustan Sugar Mills',
            'Saurashtra Agro Traders', 'Maruti Polymers', 'Bharat Cotton Co',
            'Krishna Rice Mills', 'Ramdev Iron & Steel', 'Laxmi Garment Exports',
        ];
        return $pool[array_rand($pool)].' '.random_int(1, 99);
    }

    /**
     * @return string
     */
    private function fakerDescription(): string
    {
        static $pool = [
            'Cotton bales', 'Cement bags', 'Steel rods',
            'Fertilizer sacks', 'Textile rolls', 'Sugar bags',
            'Rice bags', 'Wheat bags', 'Machinery parts',
            'Tiles and marble', 'Spare parts', 'Garments',
        ];
        return $pool[array_rand($pool)];
    }

    /**
     * Generate a plausible (but not checksummed) 15-char GSTIN.
     *
     * @return string
     */
    private function fakerGstin(): string
    {
        if (random_int(0, 9) < 3) {
            return null; // 30% have no GSTIN (small / unregd)
        }
        $stateCode = random_int(1, 37);
        $pan = '';
        for ($i = 0; $i < 5; $i++) $pan .= chr(65 + random_int(0, 25));
        $pan .= str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $pan .= chr(65 + random_int(0, 25));
        $chk = chr(65 + random_int(0, 25));
        return sprintf('%02d%s1Z%s', $stateCode, $pan, $chk);
    }

    /**
     * Random float with a given precision.
     */
    private function fakerRandomFloat(float $min, float $max, int $decimals = 2): float
    {
        $factor = 10 ** $decimals;
        return round(random_int((int)($min * $factor), (int)($max * $factor)) / $factor, $decimals);
    }

    private function fakerNumberBetween(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    private function fakerDateBetween(string $start, string $end): string
    {
        $startTs = strtotime($start);
        $endTs   = strtotime($end);
        $ts      = random_int($startTs, $endTs);
        return date('Y-m-d', $ts);
    }
}
