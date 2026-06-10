<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Branch;
use Spatie\Permission\Models\Role;

class AuthTestSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure roles exist
        $roles = ['SuperAdmin', 'Admin', 'Manager', 'Staff', 'Viewer'];
        foreach ($roles as $r) {
            Role::firstOrCreate(['name' => $r]);
        }

        // Ensure branches exist
        $branchData = [
            ['branch_name' => 'Rajkot',        'branch_code' => 'RJ', 'gr_prefix' => 'AA'],
            ['branch_name' => 'Kashmore Gate', 'branch_code' => 'KG', 'gr_prefix' => 'CG'],
            ['branch_name' => 'Navagam',       'branch_code' => 'NV', 'gr_prefix' => 'NV'],
            ['branch_name' => 'Dayabasti',     'branch_code' => 'DB', 'gr_prefix' => 'DB'],
            ['branch_name' => 'Swarup Nagar',  'branch_code' => 'SN', 'gr_prefix' => 'SN'],
            ['branch_name' => 'Shapar (1)',    'branch_code' => 'S1', 'gr_prefix' => 'S1'],
            ['branch_name' => 'Shapar (2)',    'branch_code' => 'S2', 'gr_prefix' => 'S2'],
        ];

        foreach ($branchData as $b) {
            Branch::firstOrCreate(['branch_name' => $b['branch_name']], $b);
        }

        // Create one SuperAdmin (no branch restriction)
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@sxpress.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password123'),
                'office'   => 'Rajkot',
                'is_active' => true,
            ]
        );
        $superAdmin->syncRoles(['SuperAdmin']);
        $this->command->info("SuperAdmin: superadmin@sxpress.com / password123");

        // Create one Admin per branch
        $branches = Branch::pluck('branch_name')->toArray();
        foreach ($branches as $branchName) {
            $admin = User::updateOrCreate(
                ['email' => "admin@{$branchName}.sxpress"],
                [
                    'name'     => "Admin ({$branchName})",
                    'password' => Hash::make('password123'),
                    'office'   => $branchName,
                    'is_active' => true,
                ]
            );
            $admin->syncRoles(['Admin']);
            $this->command->info("Admin: admin@{$branchName}.sxpress.com / password123 ({$branchName})");
        }

        // Create one Manager
        $manager = User::updateOrCreate(
            ['email' => 'manager@sxpress.com'],
            [
                'name'     => 'Branch Manager',
                'password' => Hash::make('password123'),
                'office'   => 'Rajkot',
                'is_active' => true,
            ]
        );
        $manager->syncRoles(['Manager']);
        $this->command->info("Manager: manager@sxpress.com / password123 (Rajkot)");

        // Create one Staff
        $staff = User::updateOrCreate(
            ['email' => 'staff@sxpress.com'],
            [
                'name'     => 'Branch Staff',
                'password' => Hash::make('password123'),
                'office'   => 'Rajkot',
                'is_active' => true,
            ]
        );
        $staff->syncRoles(['Staff']);
        $this->command->info("Staff: staff@sxpress.com / password123 (Rajkot)");

        // Create one Viewer
        $viewer = User::updateOrCreate(
            ['email' => 'viewer@sxpress.com'],
            [
                'name'     => 'Read Only Viewer',
                'password' => Hash::make('password123'),
                'office'   => 'Rajkot',
                'is_active' => true,
            ]
        );
        $viewer->syncRoles(['Viewer']);
        $this->command->info("Viewer: viewer@sxpress.com / password123 (Rajkot)");

        // Create one INACTIVE user (for testing is_active block)
        $inactive = User::updateOrCreate(
            ['email' => 'inactive@sxpress.com'],
            [
                'name'     => 'Inactive User',
                'password' => Hash::make('password123'),
                'office'   => 'Rajkot',
                'is_active' => false,
            ]
        );
        $inactive->syncRoles(['Staff']);
        $this->command->info("INACTIVE: inactive@sxpress.com / password123 (will be BLOCKED at login)");
    }
}