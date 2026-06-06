<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the modernized `drivers` table.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000081_create_drivers_table.php
 *
 *   Columns: name, license (unique), address, mobile1, mobile2,
 *            license_expiry, is_active, deleted_at, created_by_id,
 *            updated_by_id, timestamps
 *
 *   Drivers are split from the legacy `truckdrivers` table per the
 *   ERD — a driver can be reassigned to a different truck over time
 *   via the `truck_assignments` pivot.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Driver::factory()->create(['license' => 'GJ-03-20180012345']);
 *     Driver::factory()->count(50)->create();
 *     Driver::factory()->expiredLicense()->create();
 */
class DriverFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Driver::class;

    /**
     * Indian names + addresses (Gujarat / North-Indian transport hubs).
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return \Faker\Factory::create('en_IN');
    }

    /**
     * State — the driver has an expired license (compliance flag).
     *
     * @return self
     */
    public function expiredLicense(): self
    {
        return $this->state(fn () => [
            'license_expiry' => now()->subDays(random_int(30, 365)),
        ]);
    }

    /**
     * State — the driver is inactive.
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
     * Indian driving licenses are 15 chars (state code, RTO, year, 7 digits).
     * We don't validate against the official format — we generate ones
     * that *look* real for demo purposes.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $stateCode = $this->faker->randomElement(['GJ', 'MH', 'RJ', 'UP', 'DL', 'KA', 'TN', 'MP', 'HR', 'PB']);
        $rtoCode   = $this->faker->numerify('##');
        $year      = $this->faker->numberBetween(2000, (int) date('Y'));
        $serial    = $this->faker->numerify('#######');
        $license   = "$stateCode-$rtoCode-$year-$serial";

        return [
            'name'           => $this->faker->name(),
            'license'        => $license,
            'address'        => $this->faker->streetAddress().", "
                              .$this->faker->city().", "
                              .$this->faker->state()." - "
                              .$this->faker->numerify('######'),
            'mobile1'        => $this->faker->numerify('+91##########'),
            'mobile2'        => $this->faker->optional(0.3)->numerify('+91##########'),
            'license_expiry' => now()->addYears(random_int(1, 5)),
            'is_active'      => true,
        ];
    }
}
