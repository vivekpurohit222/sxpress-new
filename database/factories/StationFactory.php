<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the **non-existent** `stations` table.
 *
 * --------------------------------------------------------------------------
 * Status
 * --------------------------------------------------------------------------
 * There is no `stations` table in the current schema
 * (database/migrations/*, database/reconstructed_migrations/*) and
 * none in the modernized ERD (docs/erd.md §2).
 *
 * The user's seed request listed "Stations" — this factory exists so
 * the corresponding `StationSeeder` can compile and run, but the
 * seeder guards on `Schema::hasTable('stations')` and logs a skip.
 *
 * Note: in this codebase, "stations" might be a synonym for
 * `branches` (the existing first-class office master) — see
 * `BranchSeeder` for the actual office list. The seeder report
 * (docs/seeder-report.md) explains the relationship.
 *
 * Suggested future schema (for reference — not created here):
 *
 *   Schema::create('stations', function (Blueprint $t) {
 *       $t->bigIncrements('id');
 *       $t->string('code', 20)->unique();
 *       $t->string('name', 150);
 *       $t->unsignedBigInteger('branch_id')->nullable();
 *       $t->decimal('latitude', 10, 7)->nullable();
 *       $t->decimal('longitude', 10, 7)->nullable();
 *       $t->boolean('is_active')->default(true);
 *       $t->timestamps();
 *   });
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     StationFactory::new()->create(['name' => 'Rajkot Cross-Dock']);
 *     StationFactory::new()->count(15)->create();
 *     StationFactory::new()->atBranch($branchId)->create();
 */
class StationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * No model class exists yet. The seeder is responsible for
     * detecting the table's absence and logging a skip.
     *
     * @var string|null
     */
    protected $model = \App\Models\Station::class;

    /**
     * State — station is homed to a specific branch.
     *
     * @param  int  $branchId
     * @return self
     */
    public function atBranch(int $branchId): self
    {
        return $this->state(fn () => ['branch_id' => $branchId]);
    }

    /**
     * Define the model's default state.
     *
     * Returns a payload compatible with the SUGGESTED schema (above).
     * The seeder that uses this factory MUST guard on
     * `Schema::hasTable('stations')` — calling this factory without
     * the table present will raise a SQL error.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Cross-Dock Terminal', 'Container Yard', 'Loading Bay',
            'Weighbridge', 'Bonded Warehouse', 'Truck Terminal',
            'Container Freight Station', 'Inland Container Depot',
        ]);

        return [
            'code'      => 'STN-'.strtoupper($this->faker->unique()->bothify('??##')),
            'name'      => $this->faker->city().' '.$name,
            'branch_id' => null, // assigned by seeder
            'latitude'  => $this->faker->latitude(8.0, 37.0),    // India bounds (approx)
            'longitude' => $this->faker->longitude(68.0, 97.0),  // India bounds (approx)
            'is_active' => true,
        ];
    }
}
