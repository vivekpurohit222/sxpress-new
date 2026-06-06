<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Factory for the `users` table (modernized + legacy-safe).
 *
 * --------------------------------------------------------------------------
 * Schema source
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_11_082619_create_users_table.php  (legacy)
 * - database/reconstructed_migrations/2026_06_05_000060_add_branch_fks...
 *   ...adds `users.branch_id` (nullable FK to branches)
 * - database/reconstructed_migrations/2026_06_05_000030_add_soft_deletes...
 *   ...adds `deleted_at` (soft delete)
 *
 * The factory is defensive: every field is checked against the table schema
 * before being inserted, so it works against either the legacy or the
 * modernized shape.
 *
 * --------------------------------------------------------------------------
 * Usage
 * --------------------------------------------------------------------------
 *     User::factory()->create(['email' => 'admin@example.com']);
 *     User::factory()->count(50)->create();
 *     User::factory()->admin()->create();
 *     User::factory()->inactive()->create();
 *     User::factory()->atBranch($branchId)->create();
 */
class UserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = User::class;

    /**
     * Use the Indian locale so generated names, addresses, and phone
     * numbers look realistic for a Gujarat-headquartered logistics app.
     *
     * @return \Faker\Generator
     */
    protected function withFaker()
    {
        return \Faker\Factory::create('en_IN');
    }

    /**
     * Indicate the user is an admin (Role: Super Admin).
     *
     * @return self
     */
    public function admin(): self
    {
        return $this->state(fn () => [
            'email' => 'admin@sxpress.test',
        ]);
    }

    /**
     * Indicate the user is inactive.
     *
     * @return self
     */
    public function inactive(): self
    {
        return $this->state(fn () => [
            'is_active'    => false,
            'last_login_at'=> null,
        ]);
    }

    /**
     * Pin the user to a specific branch.
     *
     * @param  int  $branchId
     * @return self
     */
    public function atBranch(int $branchId): self
    {
        return $this->state(fn () => [
            'branch_id' => $branchId,
        ]);
    }

    /**
     * Pin the user to a specific office (legacy free-text column).
     *
     * @param  string  $office
     * @return self
     */
    public function atOffice(string $office): self
    {
        return $this->state(fn () => [
            'office' => $office,
        ]);
    }

    /**
     * Define the model's default state.
     *
     * Returns a defensive payload — only includes keys that exist on the
     * current `users` table. This keeps the factory safe to run on the
     * legacy schema (no `is_active`, no `branch_id`, no `phone`, etc.)
     * and on the modernized schema.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $sequence = 0;
        $sequence++;

        $payload = [
            'name'              => $this->faker->name(),
            'email'             => $this->seqEmail($sequence),
            'email_verified_at' => now()->subDays(random_int(0, 365)),
            // The model has a `setPasswordAttribute` mutator that bcrypts;
            // we still pre-hash here to be safe in tests.
            'password'          => Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];

        // Legacy column — preserved on the modernized table.
        if (\Schema::hasColumn('users', 'office')) {
            $payload['office'] = $this->faker->randomElement([
                'Rajkot', 'Kashmore Gate', 'Dayabasti', 'Swarup Nagar',
                'Navagam', 'Shapar (1)', 'Shapar (2)',
            ]);
        }

        // Modernized columns.
        if (\Schema::hasColumn('users', 'phone')) {
            $payload['phone'] = $this->faker->numerify('+91##########');
        }
        if (\Schema::hasColumn('users', 'is_active')) {
            $payload['is_active'] = true;
        }
        if (\Schema::hasColumn('users', 'last_login_at')) {
            $payload['last_login_at'] = now()->subDays(random_int(0, 30));
        }

        return $payload;
    }

    /**
     * Generate a unique, sequential email so duplicate-factory calls in
     * the same second don't collide.
     *
     * @param  int  $sequence
     * @return string
     */
    private function seqEmail(int $sequence): string
    {
        return sprintf(
            'user%04d_%s@sxpress.test',
            $sequence,
            Str::lower(Str::random(6))
        );
    }
}
