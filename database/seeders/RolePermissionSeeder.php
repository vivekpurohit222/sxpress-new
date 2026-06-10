<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Simplified Role & Permission Seeder.
 * 5 roles, 10 permissions.
 */
class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('roles') || !Schema::hasTable('permissions')) {
            $this->command?->warn('[RolePermissionSeeder] Skipped — Spatie tables not present.');
            return;
        }

        app()['cache']->forget('spatie.permission.cache');

        // 5 Roles
        foreach (['SuperAdmin', 'Admin', 'Manager', 'Staff', 'Viewer'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }

        // 10 Permissions
        $permissions = [
            'manage-gr',
            'manage-gatepass',
            'manage-challan',
            'manage-freight',
            'manage-masters',
            'manage-users',
            'manage-branches',
            'view-reports',
            'manage-pod',
            'manage-settings',
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        // SuperAdmin: all
        Role::findByName('SuperAdmin')->syncPermissions(Permission::all());

        // Admin: operational + users
        Role::findByName('Admin')->syncPermissions([
            'manage-gr', 'manage-gatepass', 'manage-challan', 'manage-freight',
            'manage-masters', 'manage-users', 'view-reports', 'manage-pod',
        ]);

        // Manager: operational
        Role::findByName('Manager')->syncPermissions([
            'manage-gr', 'manage-gatepass', 'manage-challan', 'manage-freight',
            'view-reports', 'manage-pod',
        ]);

        // Staff: day-to-day
        Role::findByName('Staff')->syncPermissions([
            'manage-gr', 'manage-gatepass', 'manage-challan', 'manage-pod',
        ]);

        // Viewer: no permissions (read-only via role middleware)
        Role::findByName('Viewer')->syncPermissions([]);

        $this->command?->info('[RolePermissionSeeder] Done. 5 roles, ' . count($permissions) . ' permissions.');
    }
}
