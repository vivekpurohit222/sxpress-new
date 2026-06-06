<?php

namespace Database\Factories;

use App\Models\Freight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the `frieghts` (Freight Memos) table — legacy schema.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_08_120739_create_frieghts_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000040_rename_legacy_columns.php
 *   (renames `balance_to_sn` → `balance_due`)
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Freight::factory()->create();
 *     Freight::factory()->count(80)->create();
 *     Freight::factory()->forTruck($truckId)->create();
 */
class FreightFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Freight::class;

    /**
     * State — pin to a specific truck (modernized FK).
     *
     * @param  int  $truckId
     * @return self
     */
    public function forTruck(int $truckId): self
    {
        return $this->state(fn () => ['truck_id' => $truckId]);
    }

    /**
     * Define the model's default state.
     *
     * `entry_1..4` are the four "line items" of the FM (the most
     * likely interpretation per docs/database-reconstruction-report.md §6.3).
     * `total_amount` is the sum of the entry amounts.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $entry1 = $this->faker->randomFloat(2, 0, 5000);
        $entry2 = $this->faker->randomFloat(2, 0, 3000);
        $entry3 = $this->faker->randomFloat(2, 0, 2000);
        $entry4 = $this->faker->randomFloat(2, 0, 1000);
        $total  = $entry1 + $entry2 + $entry3 + $entry4;

        $truckFreight = $this->faker->randomFloat(2, 5000, 100000);
        $commission   = round($truckFreight * $this->faker->randomFloat(2, 0.05, 0.12), 2);
        $otherCharges = $this->faker->randomFloat(2, 0, 2000);
        $extra        = $this->faker->randomFloat(2, 0, 1500);
        $balance      = $truckFreight - $commission - $otherCharges - $extra;

        $offices = ['Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar', 'Navagam', 'Shapar (1)', 'Shapar (2)'];

        $payload = [
            'fm_no'          => $this->faker->unique()->bothify('FM-####'),
            'fm_date'        => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'from_dest'      => $this->faker->randomElement($offices),
            'to_dest'        => $this->faker->randomElement($offices),
            'truck_no'       => $this->faker->regexify('[A-Z]{2}-[0-9]{2}-[A-Z]{2}-[0-9]{4}'),
            'entry_1'        => $this->faker->randomElement(['Loading charges', 'Unloading charges', 'Detention', 'Warai']),
            'entry_1_amount' => $entry1,
            'entry_2'        => $this->faker->randomElement(['Toll', 'RTO', 'Weighbridge', 'Penalty']),
            'entry_2_amount' => $entry2,
            'entry_3'        => $this->faker->optional(0.5)->randomElement(['Mamul', 'Dharmada', 'Bhatta']),
            'entry_3_amount' => $entry3,
            'entry_4'        => $this->faker->optional(0.3)->randomElement(['Other', 'Advance', 'TDS']),
            'entry_4_amount' => $entry4,
            'total_amount'   => $total,
            'truck_freight'  => $truckFreight,
            'commission'     => $commission,
            'other_charges'  => $otherCharges,
            'extra'          => $extra,
            'note'           => $this->faker->optional(0.3)->sentence(),
        ];

        // Modernized renames.
        if (\Schema::hasColumn('frieghts', 'balance_due')) {
            $payload['balance_due'] = $balance;
        } else {
            $payload['balance_to_sn'] = $balance;
        }

        // Modernized FK.
        if (\Schema::hasColumn('frieghts', 'truck_id')) {
            $payload['truck_id'] = null;
        }

        return $payload;
    }
}
