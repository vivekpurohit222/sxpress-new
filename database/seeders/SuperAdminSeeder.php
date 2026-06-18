<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the single super_admin user.
 * Email: admin@sxpress.com, Password: password
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('users')) {
            $this->command?->warn('[SuperAdminSeeder] Skipped — users table does not exist.');
            return;
        }

        User::updateOrCreate(
            ['email' => 'admin@sxpress.com'],
            [
                'name'      => 'Super Admin',
                'password'  => 'password',
                'role'      => 'super_admin',
                'office'    => null,
                'branch_id' => null,
                'phone'     => '+919999900001',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('[SuperAdminSeeder] Super Admin created: admin@sxpress.com / password');
    }
}
