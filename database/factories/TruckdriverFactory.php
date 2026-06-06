<?php

namespace Database\Factories;

use App\Models\truckdriver;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the legacy `truckdrivers` table.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_09_095555_create_truckdrivers_table.php
 * - database/reconstructed_migrations/2026_06_05_000011_fix_column_types.php
 *   (driver_address widened to TEXT)
 * - database/reconstructed_migrations/2026_06_05_000020_widen_string_columns.php
 * - database/reconstructed_migrations/2026_06_05_000030_add_soft_deletes_and_audit_columns.php
 *
 * Note: this is the LEGACY combined table (truck + driver on one row).
 * The modernized replacement is two tables:
 *   - `trucks`  (the vehicle)
 *   - `drivers` (the human)
 * linked via the `truck_assignments` pivot.
 *
 * The `truckdrivers` factory is preserved because the existing
 * application code reads from this table.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     truckdriver::factory()->create();
 *     truckdriver::factory()->count(50)->create();
 *     truckdriver::factory()->inactive()->create();
 */
class TruckdriverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = truckdriver::class;

    /**
     * State — driver is inactive.
     *
     * @return self
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stateCode = $this->faker->randomElement(['GJ', 'MH', 'RJ', 'UP', 'DL', 'KA', 'TN', 'MP', 'HR', 'PB']);
        $rtoCode   = $this->faker->numerify('##');
        $letters   = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPRSTUVWXYZ'), 0, 2));
        $digits    = $this->faker->numerify('####');
        $truckNo   = "$stateCode-$rtoCode-$letters-$digits";

        $license   = "$stateCode-$rtoCode-".$this->faker->numberBetween(2000, (int) date('Y')).'-'.$this->faker->numerify('#######');

        $payload = [
            'driver_name'    => $this->faker->name(),
            'truck_no'       => $truckNo,
            'license'        => $license,
            'driver_address' => $this->faker->streetAddress().', '.$this->faker->city().', '.$this->faker->state().' - '.$this->faker->numerify('######'),
            'mobile_no1'     => $this->faker->unique()->numerify('+91##########'),
            'mobile_no2'     => $this->faker->optional(0.25)->numerify('+91##########'),
        ];

        if (\Schema::hasColumn('truckdrivers', 'is_active')) {
            $payload['is_active'] = true;
        }

        return $payload;
    }
}
