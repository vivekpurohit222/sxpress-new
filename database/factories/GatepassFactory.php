<?php

namespace Database\Factories;

use App\Models\gatepass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the `gatepasses` table — legacy schema.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_10_29_082717_create_gatepasses_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000021_add_status_columns.php
 * - database/reconstructed_migrations/2026_06_05_000040_rename_legacy_columns.php
 *   (renames `m_s` → `consignor`, `dc_amount` → `delivery_charge`)
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     gatepass::factory()->create();
 *     gatepass::factory()->count(100)->create();
 *     gatepass::factory()->forGr($grId)->create();
 */
class GatepassFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = gatepass::class;

    /**
     * State — pin the gatepass to a specific GR (modernized FK).
     *
     * @param  int  $grId
     * @return self
     */
    public function forGr(int $grId): self
    {
        return $this->state(fn () => ['gr_id' => $grId]);
    }

    /**
     * Define the model's default state.
     *
     * gp_no is auto-incremented locally (the legacy code does
     * `$gp_no++; if ($gp_no == 1000) $gp_no = 0;`). The factory uses
     * a unique number to avoid collisions in a seeder run.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $frieght = $this->faker->randomFloat(2, 500, 25000);
        $labour  = $this->faker->randomFloat(2, 0, 1000);
        $other   = $this->faker->randomFloat(2, 0, 500);
        $delivery = $this->faker->randomFloat(2, 0, 800);
        $total   = $frieght + $labour + $other + $delivery;

        $payload = [
            'gp_no'           => $this->faker->unique()->numberBetween(1, 9999),
            'gp_date'         => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'from_dest'       => $this->faker->randomElement(['Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar', 'Navagam', 'Shapar (1)', 'Shapar (2)']),
            'to_dest'         => $this->faker->randomElement(['Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar', 'Navagam', 'Shapar (1)', 'Shapar (2)']),
            'gr_no'           => $this->faker->unique()->bothify('??-#####'),
            'weight'          => $this->faker->randomFloat(3, 50, 5000),
            'nugs'            => $this->faker->numberBetween(1, 200),
            'pm'              => $this->faker->randomElement(['Paid', 'To Pay', 'TBB', 'FOC']),
            'frieght_amount'  => $frieght,
            'labour_amount'   => $labour,
            'other'           => $other,
            'total_amount'    => $total,
            'note'            => $this->faker->optional(0.3)->sentence(),
        ];

        // Modernized renames.
        if (\Schema::hasColumn('gatepasses', 'consignor')) {
            $payload['consignor'] = $this->faker->company();
        } else {
            $payload['m_s'] = $this->faker->company();
        }
        if (\Schema::hasColumn('gatepasses', 'delivery_charge')) {
            $payload['delivery_charge'] = $delivery;
        } else {
            $payload['dc_amount'] = $delivery;
        }

        // gr_id (modernized FK) is optional; the seeder will set it for
        // linked gatepasses.
        if (\Schema::hasColumn('gatepasses', 'gr_id')) {
            $payload['gr_id'] = null;
        }

        if (\Schema::hasColumn('gatepasses', 'status')) {
            $payload['status'] = $this->faker->randomElement(['booked', 'in_transit', 'delivered', 'closed']);
        }

        return $payload;
    }
}
