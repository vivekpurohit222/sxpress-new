<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Role & Permission Seeder.
 *
 * 3 Roles:
 *  - SuperAdmin   → Full system access (accounting, settings, all branches, user management)
 *  - BranchManager → Operational access scoped to own branch (GR, Gatepass, Challan, Freight Memo, Reports, POD)
 *  - Agent        → Day-to-day booking scoped to own branch (GR, Gatepass, Challan, POD)
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

        // 3 Roles
        foreach (['SuperAdmin', 'BranchManager', 'Agent'] as $r) {
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

        // SuperAdmin: all permissions
        Role::findByName('SuperAdmin')->syncPermissions(Permission::all());

        // BranchManager: operational + reports (scoped to branch via OfficeScopeTrait)
        Role::findByName('BranchManager')->syncPermissions([
            'manage-gr', 'manage-gatepass', 'manage-challan', 'manage-freight',
            'view-reports', 'manage-pod',
        ]);

        // Agent: day-to-day booking only
        Role::findByName('Agent')->syncPermissions([
            'manage-gr', 'manage-gatepass', 'manage-challan', 'manage-pod',
        ]);

        $this->command?->info('[RolePermissionSeeder] Done. 3 roles, ' . count($permissions) . ' permissions.');
    }
}
