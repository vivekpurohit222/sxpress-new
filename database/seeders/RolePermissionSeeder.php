<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Seed the canonical set of Roles and Permissions (Spatie).
 *
 * --------------------------------------------------------------------------
 * Source of truth
 * --------------------------------------------------------------------------
 * - database/migrations/2020_10_22_120538_create_permission_tables.php
 *   (Spatie schema — roles, permissions, role_has_permissions, model_has_*)
 * - docs/erd.md                 §2  (USER-ROLE-PERMISSION diagram)
 * - docs/security-audit.md      §6  (RBAC structure)
 * - docs/master-execution-roadmap.md  §5.23 (seed canonical RBAC)
 *
 * --------------------------------------------------------------------------
 * What this seeder does
 * --------------------------------------------------------------------------
 * 1. Creates 4 canonical roles:
 *      - Super Admin   (every permission, every guard)
 *      - Branch Manager
 *      - Operator
 *      - Viewer
 *
 * 2. Creates the canonical permission set used by the application.
 *    The names follow Spatie's "verb noun" convention.
 *
 * 3. Wires the role → permission grants.
 *
 * 4. Avoids duplicates — uses firstOrCreate on name + guard_name.
 *
 * 5. Skips gracefully if the Spatie tables don't exist (a fresh
 *    install that hasn't run the permission migration yet).
 *
 * --------------------------------------------------------------------------
 * Idempotency
 * --------------------------------------------------------------------------
 * Roles and permissions are matched by (name, guard_name). Re-running
 * the seeder is a no-op (existing rows are skipped).
 *
 * --------------------------------------------------------------------------
 * NOT in scope
 * --------------------------------------------------------------------------
 * - This seeder does NOT assign roles to any user. That's the job
 *   of `UserSeeder`.
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('permissions')) {
            $this->command?->warn('[RolePermissionSeeder] Skipped — Spatie tables are not present. Run `php artisan migrate` first.');
            return;
        }

        // The Spatie permission cache can mask inserts done outside the
        // package. Reset it before mutating.
        app()['cache']->forget('spatie.permission.cache');

        $permissions = $this->canonicalPermissions();
        foreach ($permissions as $name) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
            );
        }

        $roleMatrix = $this->rolePermissionMatrix();
        foreach ($roleMatrix as $roleName => $permsForRole) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
            );

            // `syncPermissions` is idempotent: it removes any permission
            // we no longer want to grant and adds the ones we do.
            $role->syncPermissions($permsForRole);
        }

        $this->command?->info(sprintf(
            '[RolePermissionSeeder] %d roles, %d permissions wired.',
            count($roleMatrix),
            count($permissions),
        ));
    }

    /**
     * Return the canonical permission names. Grouped logically but
     * returned as a flat list (Laravel doesn't care about groups).
     *
     * @return array<int, string>
     */
    private function canonicalPermissions(): array
    {
        return [
            // GR
            'view gr', 'create gr', 'edit gr', 'delete gr', 'print gr', 'export gr',

            // Gatepass
            'view gatepass', 'create gatepass', 'edit gatepass', 'delete gatepass', 'print gatepass',

            // Challan
            'view challan', 'create challan', 'edit challan', 'delete challan', 'print challan',

            // Freight Memo
            'view freight', 'create freight', 'edit freight', 'delete freight', 'print freight',

            // Truck & driver
            'view truck', 'create truck', 'edit truck', 'delete truck',
            'view driver', 'create driver', 'edit driver', 'delete driver',

            // Customer & Vendor
            'view customer', 'create customer', 'edit customer', 'delete customer',
            'view vendor', 'create vendor', 'edit vendor', 'delete vendor',

            // Reports
            'view report', 'export report',

            // Settings
            'view setting', 'edit setting',

            // User management
            'view user', 'create user', 'edit user', 'delete user', 'assign role',
        ];
    }

    /**
     * Return role → permission grants.
     *
     * @return array<string, array<int, string>>
     */
    private function rolePermissionMatrix(): array
    {
        $all = $this->canonicalPermissions();

        $branchManager = [
            'view gr', 'create gr', 'edit gr', 'print gr', 'export gr',
            'view gatepass', 'create gatepass', 'edit gatepass', 'print gatepass',
            'view challan', 'create challan', 'edit challan', 'print challan',
            'view freight', 'create freight', 'edit freight', 'print freight',
            'view truck', 'view driver',
            'view customer', 'create customer', 'edit customer',
            'view vendor', 'create vendor', 'edit vendor',
            'view report', 'export report',
            'view user',
        ];

        $operator = [
            'view gr', 'create gr', 'edit gr', 'print gr',
            'view gatepass', 'create gatepass', 'edit gatepass', 'print gatepass',
            'view challan', 'create challan', 'edit challan', 'print challan',
            'view freight', 'create freight', 'edit freight', 'print freight',
            'view truck', 'view driver',
            'view customer', 'create customer',
            'view vendor',
            'view report',
        ];

        $viewer = [
            'view gr', 'print gr',
            'view gatepass', 'print gatepass',
            'view challan', 'print challan',
            'view freight', 'print freight',
            'view truck', 'view driver',
            'view customer', 'view vendor',
            'view report',
        ];

        return [
            'Super Admin'    => $all,
            'Branch Manager' => $branchManager,
            'Operator'       => $operator,
            'Viewer'         => $viewer,
        ];
    }
}
