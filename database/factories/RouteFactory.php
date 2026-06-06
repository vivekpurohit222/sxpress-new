<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the **non-existent** `routes` table.
 *
 * --------------------------------------------------------------------------
 * Status
 * --------------------------------------------------------------------------
 * There is no `routes` table in the current schema
 * (database/migrations/*, database/reconstructed_migrations/*) and
 * none in the modernized ERD (docs/erd.md §2).
 *
 * The user's seed request listed "Routes" — this factory exists so
 * the corresponding `RouteSeeder` can compile and run, but the
 * seeder guards on `Schema::hasTable('routes')` and logs a skip.
 *
 * When/if a `routes` table is added in a future migration, this
 * factory will plug in by adding a `protected $model` binding.
 *
 * Suggested future schema (for reference — not created here):
 *
 *   Schema::create('routes', function (Blueprint $t) {
 *       $t->bigIncrements('id');
 *       $t->string('code', 20)->unique();
 *       $t->string('name', 150);
 *       $t->unsignedBigInteger('origin_branch_id');
 *       $t->unsignedBigInteger('destination_branch_id');
 *       $t->decimal('distance_km', 8, 2)->nullable();
 *       $t->unsignedSmallInteger('transit_hours')->nullable();
 *       $t->boolean('is_active')->default(true);
 *       $t->timestamps();
 *   });
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     RouteFactory::new()->create(['name' => 'RJKT-DYBS Express']);
 *     RouteFactory::new()->count(10)->create();
 *     RouteFactory::new()->between('RJKT', 'DYBS')->create();
 */
class RouteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * No model class exists yet. The seeder is responsible for
     * detecting the table's absence and logging a skip.
     *
     * @var string|null
     */
    protected $model = \App\Models\Route::class;

    /**
     * State — a route between two specific branches.
     *
     * @param  string  $originCode
     * @param  string  $destinationCode
     * @return self
     */
    public function between(string $originCode, string $destinationCode): self
    {
        return $this->state(fn () => [
            'origin_branch_id'      => $originCode,       // see seeder for resolution
            'destination_branch_id' => $destinationCode,
        ]);
    }

    /**
     * Define the model's default state.
     *
     * Returns a payload compatible with the SUGGESTED schema (above).
     * The seeder that uses this factory MUST guard on
     * `Schema::hasTable('routes')` — calling this factory without
     * the table present will raise a SQL error.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $pair = $this->faker->randomElement([
            ['RJKT', 'KASH'],
            ['RJKT', 'DYBS'],
            ['NVGM', 'SWNP'],
            ['SHP1', 'SHP2'],
            ['RJKT', 'NVGM'],
            ['KASH', 'DYBS'],
            ['SWNP', 'DYBS'],
        ]);

        return [
            'code'                  => strtoupper(implode('-', $pair)).'-R'.$this->faker->unique()->numberBetween(1, 999),
            'name'                  => $this->faker->randomElement([
                'Direct Express', 'Night Service', 'Daily Service', 'Standard Service',
                'Premium Service', 'Economy Service', 'Container Service',
            ]).' — '.$pair[0].' ↔ '.$pair[1],
            'origin_branch_id'      => $pair[0],
            'destination_branch_id' => $pair[1],
            'distance_km'           => $this->faker->randomFloat(2, 100, 2500),
            'transit_hours'         => $this->faker->numberBetween(6, 72),
            'is_active'             => true,
        ];
    }
}
