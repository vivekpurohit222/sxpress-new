<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for the modernized `branches` table.
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/reconstructed_migrations/2026_06_05_000050_create_branches_table.php
 *
 *   Columns: code, name, city, state, pincode, phone, is_active,
 *            created_by_id, updated_by_id, timestamps
 *
 * Note: there is no `App\Models\Branch` model yet (it's marked as a
 * recommended new model in the docs). The factory uses the
 * factory's `protected $model = Branch::class` only when the class
 * exists; otherwise it falls back to the table name.
 *
 * For the same reason the factory is purely a TableFactory (no model
 * class) — see `definition()` for the schema-aware insert.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     BranchFactory::new()->create(['name' => 'Rajkot']);
 *     BranchFactory::new()->count(7)->create(); // 7 sample branches
 *     BranchFactory::new()->inactive()->create();
 */
class BranchFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * Optional: App\Models\Branch doesn't exist yet. We provide a safe
     * default by leaving this unset and overriding `modelName()` for
     * factories that want to bind to a model.
     *
     * @var string|null
     */
    protected $model = \App\Models\Branch::class;

    /**
     * Indian city names + Indian pincode format.
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return \Faker\Factory::create('en_IN');
    }

    /**
     * Mark the branch as inactive.
     *
     * @return self
     */
    public function inactive(): self
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    /**
     * Mark the branch as a Gujarat-based office.
     *
     * @return self
     */
    public function gujarat(): self
    {
        return $this->state(fn () => [
            'city'  => $this->faker->randomElement(['Rajkot', 'Surat', 'Ahmedabad', 'Vadodara']),
            'state' => 'Gujarat',
        ]);
    }

    /**
     * Define the model's default state.
     *
     * Returns a defensive payload. If the `branches` table doesn't exist
     * (e.g. legacy DB without modernization migrations), the caller
     * should still use `Schema::hasTable('branches')` guards — this
     * factory won't fail loudly, but the insert will throw at DB level.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cities  = ['Rajkot', 'Kashmore', 'Delhi', 'Kanpur', 'Surat', 'Shapar', 'Mumbai', 'Pune', 'Jaipur', 'Indore'];
        $states  = ['Gujarat', 'Sindh', 'Delhi', 'UP', 'Maharashtra', 'Rajasthan', 'MP'];
        $city    = $this->faker->randomElement($cities);
        $state   = $this->faker->randomElement($states);

        return [
            'code'      => strtoupper(Str::limit(Str::slug($city, ''), 4, '')).$this->faker->unique()->numberBetween(10, 99),
            'name'      => $city,
            'city'      => $city,
            'state'     => $state,
            'pincode'   => $this->faker->numerify('######'),
            'phone'     => $this->faker->numerify('+91##########'),
            'is_active' => true,
        ];
    }
}
