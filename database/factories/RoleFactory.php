<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for the `roles` table (Spatie).
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_10_22_120538_create_permission_tables.php
 *
 *   Columns: id, name, guard_name, timestamps
 *
 *   The Spatie schema is intentionally minimal. Roles are paired with
 *   permissions in the pivot `role_has_permissions` table — see
 *   `RolePermissionSeeder` for the canonical mapping.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     Role::factory()->create(['name' => 'Operator']);
 *     Role::factory()->count(3)->create();
 *     Role::factory()->admin()->create();
 */
class RoleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = \Spatie\Permission\Models\Role::class;

    /**
     * State — the role is the Super Admin.
     *
     * @return self
     */
    public function admin(): self
    {
        return $this->state(fn () => ['name' => 'Super Admin']);
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->jobTitle();

        return [
            'name'       => Str::title($name),
            'guard_name' => 'web',
        ];
    }
}
