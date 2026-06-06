<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for the modernized `customers` table.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000053_create_customers_table.php
 *
 *   Columns: code (unique), name, address, gst_no, pan_no, phone, email,
 *            is_active, deleted_at, created_by_id, updated_by_id, timestamps
 *
 *   The `code` column is auto-generated as C0001, C0002, … when null —
 *   see the seeder for the exact counter wiring.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Customer::factory()->create(['name' => 'Acme Logistics']);
 *     Customer::factory()->count(50)->create();
 *     Customer::factory()->gstRegistered()->create();
 *     Customer::factory()->inactive()->create();
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \App\Models\Customer::class;

    /**
     * Indian corporate-style names, addresses, and phone numbers.
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return \Faker\Factory::create('en_IN');
    }

    /**
     * State — the customer is GST-registered (has a valid 15-char GSTIN).
     *
     * @return self
     */
    public function gstRegistered(): self
    {
        return $this->state(fn () => [
            'gst_no' => $this->generateGstin(),
        ]);
    }

    /**
     * State — the customer is inactive.
     *
     * @return self
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * State — a corporate-style customer (Pvt Ltd, Ltd, etc.).
     *
     * @return self
     */
    public function corporate(): self
    {
        return $this->state(function () {
            $name = $this->faker->company().' '.$this->faker->randomElement(['Pvt Ltd', 'Ltd', 'LLP', 'Enterprises']);
            return ['name' => $name];
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            // `code` is null by default — the seeder or a model observer
            // assigns C0001, C0002, … using a counter table.
            'code'      => null,
            'name'      => $name,
            'address'   => $this->faker->streetAddress().",\n"
                          .$this->faker->city().", ".$this->faker->state()." - "
                          .$this->faker->numerify('######'),
            'gst_no'    => $this->faker->optional(0.7)->passthrough($this->generateGstin()),
            'pan_no'    => $this->faker->optional(0.6)->regexify('[A-Z]{5}[0-9]{4}[A-Z]{1}'),
            'phone'     => $this->faker->numerify('+91##########'),
            'email'     => $this->faker->optional(0.6)->companyEmail(),
            'is_active' => true,
        ];
    }

    /**
     * Generate a syntactically valid 15-char Indian GSTIN.
     *
     * Format: 2 digits (state) + 10 chars (PAN) + 1 (entity) + 1 (Z) + 1 (checksum)
     * We don't validate the checksum — the seed is for demo, not for filing.
     *
     * @return string
     */
    private function generateGstin(): string
    {
        $stateCode = $this->faker->numberBetween(1, 37);
        $pan       = strtoupper(Str::random(5)).$this->faker->numerify('####').strtoupper(Str::random(1));

        return sprintf(
            '%02d%s1Z%s',
            $stateCode,
            $pan,
            strtoupper(Str::random(1))
        );
    }
}
