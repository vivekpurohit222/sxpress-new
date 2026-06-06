<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the modernized `trucks` (a.k.a. "vehicles") table.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000080_create_trucks_table.php
 *
 *   Columns: truck_no (unique), owner_vendor_id, home_branch_id, make,
 *            model, year, fitness_certificate_no, fitness_expiry,
 *            insurance_no, insurance_expiry, permit_no, permit_expiry,
 *            is_active, deleted_at, created_by_id, updated_by_id, timestamps
 *
 *   The user's seed request listed "Vehicles" — we model it as the
 *   modernized `trucks` table (one row per physical vehicle, separate
 *   from `drivers` per the ERD).
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Truck::factory()->create(['truck_no' => 'GJ-03-AT-1234']);
 *     Truck::factory()->count(50)->create();
 *     Truck::factory()->expiredPermit()->create();
 *     Truck::factory()->forVendor($vendorId)->create();
 */
class VehicleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Truck::class;

    /**
     * State — the vehicle has an expired permit (compliance flag).
     *
     * @return self
     */
    public function expiredPermit(): self
    {
        return $this->state(fn () => [
            'permit_expiry' => now()->subDays(random_int(30, 365)),
        ]);
    }

    /**
     * State — the vehicle is owned by a specific vendor.
     *
     * @param  int  $vendorId
     * @return self
     */
    public function forVendor(int $vendorId): self
    {
        return $this->state(fn () => ['owner_vendor_id' => $vendorId]);
    }

    /**
     * State — the vehicle is homed at a specific branch.
     *
     * @param  int  $branchId
     * @return self
     */
    public function atBranch(int $branchId): self
    {
        return $this->state(fn () => ['home_branch_id' => $branchId]);
    }

    /**
     * Define the model's default state.
     *
     * Indian truck numbers follow a specific pattern (state code, RTO
     * code, letters, digits). We generate ones that *look* real but
     * are faker-rolled — they're not registered.
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

        $makes  = ['Tata', 'Ashok Leyland', 'Mahindra', 'Eicher', 'BharatBenz', 'Volvo', 'Scania', 'MAN', 'Isuzu'];
        $models = ['LPT 1109', 'LPT 1612', 'LPT 2518', 'E2 LCV', '1618', '2518', '2523', '3523', 'Tata 407', 'LPO'];

        $make = $this->faker->randomElement($makes);
        $model = $this->faker->randomElement($models);
        $year = $this->faker->numberBetween(2010, (int) date('Y'));

        return [
            'truck_no'              => $truckNo,
            'owner_vendor_id'       => null, // assigned by seeder
            'home_branch_id'        => null, // assigned by seeder
            'make'                  => $make,
            'model'                 => $model,
            'year'                  => $year,
            'fitness_certificate_no'=> strtoupper('FC').$this->faker->numerify('########'),
            'fitness_expiry'        => now()->addMonths(random_int(3, 24)),
            'insurance_no'          => strtoupper('INS').$this->faker->numerify('##########'),
            'insurance_expiry'      => now()->addMonths(random_int(6, 24)),
            'permit_no'             => strtoupper('PMT').$this->faker->numerify('########'),
            'permit_expiry'         => now()->addMonths(random_int(2, 24)),
            'is_active'             => true,
        ];
    }
}
