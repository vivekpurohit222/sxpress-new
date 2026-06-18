<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

/**
 * Consolidate from 5 roles (SuperAdmin, Admin, Manager, Staff, Viewer)
 * down to 3 roles (SuperAdmin, BranchManager, Agent).
 *
 * Migration mapping:
 *  - Admin → BranchManager
 *  - Manager → BranchManager
 *  - Staff → Agent
 *  - Viewer → Agent
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('roles')) return;

        // Clear Spatie permission cache
        app()['cache']->forget('spatie.permission.cache');

        // Ensure the new roles exist
        $branchManager = Role::firstOrCreate(['name' => 'BranchManager', 'guard_name' => 'web']);
        $agent = Role::firstOrCreate(['name' => 'Agent', 'guard_name' => 'web']);

        // Get old role IDs
        $adminRole = Role::where('name', 'Admin')->first();
        $managerRole = Role::where('name', 'Manager')->first();
        $staffRole = Role::where('name', 'Staff')->first();
        $viewerRole = Role::where('name', 'Viewer')->first();

        // Migrate user-role assignments: Admin/Manager → BranchManager
        if ($adminRole) {
            DB::table('model_has_roles')
                ->where('role_id', $adminRole->id)
                ->update(['role_id' => $branchManager->id]);
        }
        if ($managerRole) {
            // Only assign BranchManager if user doesn't already have it
            $existingBm = DB::table('model_has_roles')
                ->where('role_id', $branchManager->id)
                ->pluck('model_id')->toArray();

            DB::table('model_has_roles')
                ->where('role_id', $managerRole->id)
                ->whereNotIn('model_id', $existingBm)
                ->update(['role_id' => $branchManager->id]);

            // Delete any remaining (duplicate assignments)
            DB::table('model_has_roles')
                ->where('role_id', $managerRole->id)
                ->delete();
        }

        // Staff/Viewer → Agent
        if ($staffRole) {
            DB::table('model_has_roles')
                ->where('role_id', $staffRole->id)
                ->update(['role_id' => $agent->id]);
        }
        if ($viewerRole) {
            $existingAgents = DB::table('model_has_roles')
                ->where('role_id', $agent->id)
                ->pluck('model_id')->toArray();

            DB::table('model_has_roles')
                ->where('role_id', $viewerRole->id)
                ->whereNotIn('model_id', $existingAgents)
                ->update(['role_id' => $agent->id]);

            DB::table('model_has_roles')
                ->where('role_id', $viewerRole->id)
                ->delete();
        }

        // Delete the old roles
        Role::whereIn('name', ['Admin', 'Manager', 'Staff', 'Viewer'])->delete();

        // Also clean up role_has_permissions for those roles
        // (cascade should handle it, but just in case)
        if ($adminRole) DB::table('role_has_permissions')->where('role_id', $adminRole->id)->delete();
        if ($managerRole) DB::table('role_has_permissions')->where('role_id', $managerRole->id)->delete();
        if ($staffRole) DB::table('role_has_permissions')->where('role_id', $staffRole->id)->delete();
        if ($viewerRole) DB::table('role_has_permissions')->where('role_id', $viewerRole->id)->delete();

        // Clear cache again
        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        // Re-create old roles (cannot reverse user assignments reliably)
        app()['cache']->forget('spatie.permission.cache');

        foreach (['Admin', 'Manager', 'Staff', 'Viewer'] as $r) {
            Role::firstOrCreate(['name' => $r, 'guard_name' => 'web']);
        }
    }
};
