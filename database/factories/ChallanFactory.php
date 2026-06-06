<?php

namespace Database\Factories;

use App\Models\challan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the `challans` table — legacy schema.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_12_09_090031_create_challans_table.php
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 * - database/reconstructed_migrations/2026_06_05_000021_add_status_columns.php
 *
 * The legacy table uses `challan_no` as the de-facto primary key
 * (UNIQUE). Migration 000010 adds an `id` column + a real PK.
 * The factory is defensive against either shape.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     challan::factory()->create();
 *     challan::factory()->count(80)->create();
 *     challan::factory()->forTruck($truckId)->create();
 */
class ChallanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = challan::class;

    /**
     * State — pin the challan to a specific truck (modernized FK).
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
     * `challan_total` is the sum of the challan_iteams.frieght_amount
     * (computed in the application). The factory populates a plausible
     * total; the seeder re-computes it from the lines it inserts.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $offices = ['Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar', 'Navagam', 'Shapar (1)', 'Shapar (2)'];

        $payload = [
            'challan_no'    => $this->faker->unique()->bothify('CH-####'),
            'from_dest'     => $this->faker->randomElement($offices),
            'challan_date'  => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'to_dest'       => $this->faker->randomElement($offices),
            'truck_no'      => $this->faker->regexify('[A-Z]{2}-[0-9]{2}-[A-Z]{2}-[0-9]{4}'),
            'driver_name'   => $this->faker->name(),
            'license'       => $this->faker->regexify('[A-Z]{2}-[0-9]{2}-[0-9]{4}-[0-9]{7}'),
            'owner_name'    => $this->faker->company().' '.$this->faker->randomElement(['Transport', 'Trucking', 'Logistics']),
            'note'          => $this->faker->optional(0.2)->sentence(),
            'challan_total' => $this->faker->randomFloat(2, 5000, 200000),
        ];

        if (\Schema::hasColumn('challans', 'truck_id')) {
            $payload['truck_id'] = null;
        }
        if (\Schema::hasColumn('challans', 'status')) {
            $payload['status'] = $this->faker->randomElement(['booked', 'in_transit', 'delivered', 'closed']);
        }

        return $payload;
    }
}
