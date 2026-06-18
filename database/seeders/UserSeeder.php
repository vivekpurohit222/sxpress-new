<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Seed the `users` table with a canonical demo set.
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_11_11_082619_create_users_table.php  (legacy)
 * - database/reconstructed_migrations/2026_06_05_000060_add_branch_fks...
 *   (adds `users.branch_id`)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 1 super-admin (admin@sxpress.test / password).
 *
 * 2. Creates 1 user per office, with the matching `office` (legacy)
 *    AND `branch_id` (modernized) values — so the user can log in
 *    against either schema.
 *
 * 3. Assigns the matching Spatie role:
 *    - super-admin@sxpress.test → Super Admin
 *    - office admins           → Branch Manager
 *    - other users             → Operator
 *
 * 4. Adds 5 generic Operator users (no specific branch) for bulk
 *    demo data.
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Users are matched by email. Re-running this seeder updates the
 * name, password, and role assignment but does not duplicate.
 *
 * --------------------------------------------------------------------------
 * Notes on the `office` column
 * --------------------------------------------------------------------------
 * The legacy column is the de-facto tenancy key (see
 * docs/database-reconstruction-report.md §2.3). We populate it from
 * the matching `branches.name` so existing list-view code (which
 * filters on `office = 'Rajkot'`) keeps working.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('users')) {
            $this->command?->warn('[UserSeeder] Skipped — the `users` table does not exist. Run `php artisan migrate` first.');
            return;
        }

        $branches = Schema::hasTable('branches')
            ? DB::table('branches')->orderBy('id')->get()->keyBy('code')
            : collect();

        $now = now();

        // 1. Super-admin — a single user with the SuperAdmin role.
        $this->upsertUser([
            'name'     => 'Super Admin',
            'email'    => 'admin@sxpress.test',
            'password' => 'password',
            'office'   => optional($branches->get('RJKT'))->name ?? 'Rajkot - PN',
            'phone'    => '+919999900001',
            'is_active'=> true,
            'email_verified_at' => $now,
            'branch_id'=> optional($branches->get('RJKT'))->id,
        ], 'SuperAdmin');

        // 2. One BranchManager per office.
        if ($branches->isNotEmpty()) {
            $managers = [
                'RJKT' => 'Rajesh Patel',
                'KASH' => 'Aslam Khan',
                'DYBS' => 'Suresh Kumar',
                'SWNP' => 'Pradeep Verma',
                'NVGM' => 'Hitesh Shah',
                'SHP1' => 'Mahesh Joshi',
                'SHP2' => 'Dinesh Parmar',
            ];
            foreach ($managers as $code => $name) {
                $branch = $branches->get($code);
                if (! $branch) continue;
                $this->upsertUser([
                    'name'     => $name,
                    'email'    => strtolower($code).'-mgr@sxpress.test',
                    'password' => 'password',
                    'office'   => $branch->name,
                    'phone'    => $this->randomIndianPhone(),
                    'is_active'=> true,
                    'email_verified_at' => $now,
                    'branch_id'=> $branch->id,
                ], 'BranchManager');
            }
        }

        // 3. Five Agent users — used to populate GR/challan
        //    `created_by_id` audit columns with realistic data.
        for ($i = 1; $i <= 5; $i++) {
            $this->upsertUser([
                'name'     => "Agent $i",
                'email'    => "agent{$i}@sxpress.test",
                'password' => 'password',
                'office'   => optional($branches->get('RJKT'))->name ?? 'Rajkot',
                'phone'    => $this->randomIndianPhone(),
                'is_active'=> true,
                'email_verified_at' => $now,
                'branch_id'=> optional($branches->get('RJKT'))->id,
            ], 'Agent');
        }

        $this->command?->info('[UserSeeder] Demo users created (password: "password" for all).');
    }

    /**
     * Create or update a user by email, and assign the role.
     *
     * @param  array<string, mixed>  $attrs
     * @param  string                $roleName
     * @return void
     */
    private function upsertUser(array $attrs, string $roleName): void
    {
        $email = $attrs['email'];

        // Build the column list we will write — only include columns
        // that exist on the current `users` table (legacy vs.
        // modernized).
        $payload = collect($attrs)->only(
            array_intersect(
                array_keys($attrs),
                Schema::getColumnListing('users')
            )
        )->all();

        $user = User::updateOrCreate(
            ['email' => $email],
            $payload,
        );

        if (Schema::hasTable('roles')) {
            $role = Role::firstWhere('name', $roleName);
            if ($role) {
                $user->syncRoles([$role]);
            }
        }
    }

    /**
     * Generate a plausible Indian phone number (+91 prefix).
     *
     * @return string
     */
    private function randomIndianPhone(): string
    {
        return '+91'.str_pad((string) random_int(7000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }
}
