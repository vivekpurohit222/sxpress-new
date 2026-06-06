<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory for the `permissions` table (Spatie).
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_10_22_120538_create_permission_tables.php
 *
 *   Columns: id, name, guard_name, timestamps
 *
 *   Permissions are usually created in a seeder (not via factory) so
 *   that the canonical set is established exactly once. This factory
 *   exists for tests and ad-hoc seeder additions.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Permission::factory()->create(['name' => 'edit gr']);
 *     Permission::factory()->count(20)->create();
 */
class PermissionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Spatie\Permission\Models\Permission::class;

    /**
     * Define the model's default state.
     *
     * Names follow the Spatie convention of lowercase-with-spaces.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $verb = $this->faker->randomElement(['view', 'create', 'edit', 'delete', 'approve', 'export']);
        $noun = $this->faker->randomElement(['gr', 'gatepass', 'challan', 'freight', 'truck', 'driver', 'customer', 'vendor', 'report']);

        return [
            'name'       => "{$verb} {$noun}",
            'guard_name' => 'web',
        ];
    }
}
