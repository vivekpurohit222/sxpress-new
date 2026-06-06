<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for the modernized `vendors` table (truck owners).
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000054_create_vendors_table.php
 *
 *   Columns: code (unique), name, address, gst_no, pan_no, bank_account,
 *            ifsc, phone, is_active, deleted_at, created_by_id,
 *            updated_by_id, timestamps
 *
 *   The `code` column is auto-generated as V0001, V0002, … by the seeder.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Vendor::factory()->create(['name' => 'Sharma Transport']);
 *     Vendor::factory()->count(30)->create();
 *     Vendor::factory()->withBankDetails()->create();
 *     Vendor::factory()->inactive()->create();
 */
class VendorFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Vendor::class;

    /**
     * Indian names + addresses (Gujarat / Rajasthan / Maharashtra
     * transport hubs are most common in the vendor pool).
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return \Faker\Factory::create('en_IN');
    }

    /**
     * State — the vendor has full bank settlement details.
     *
     * @return self
     */
    public function withBankDetails(): self
    {
        return $this->state(fn () => [
            'bank_account' => $this->faker->numerify('####################'),
            'ifsc'         => strtoupper(Str::random(4)).'0'.$this->faker->numerify('######'),
        ]);
    }

    /**
     * State — the vendor is inactive.
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
        $surname = $this->faker->randomElement(['Sharma', 'Patel', 'Singh', 'Kumar', 'Yadav', 'Verma', 'Parmar', 'Joshi', 'Rathore', 'Chauhan']);
        $prefix  = $this->faker->randomElement(['Shree', 'Sai', 'Balaji', 'Jai', 'Shiv', 'Ganesh', 'Ram', 'Krishna', 'New', 'Modern']);
        $suffix  = $this->faker->randomElement(['Transport', 'Trucking', 'Carriers', 'Logistics', 'Roadways', 'Motors', 'Travels', 'Freight']);

        return [
            'code'      => null, // assigned by the seeder
            'name'      => "$prefix $surname $suffix",
            'address'   => $this->faker->streetAddress().",\n"
                          .$this->faker->city().", ".$this->faker->state()." - "
                          .$this->faker->numerify('######'),
            'gst_no'    => $this->faker->optional(0.6)->regexify('[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}1Z[A-Z]{1}'),
            'pan_no'    => $this->faker->optional(0.7)->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),
            'bank_account' => $this->faker->optional(0.5)->numerify('####################'),
            'ifsc'         => $this->faker->optional(0.5)->regexify('[A-Z]{4}0[A-Z0-9]{6}'),
            'phone'     => $this->faker->numerify('+91##########'),
            'is_active' => true,
        ];
    }
}
