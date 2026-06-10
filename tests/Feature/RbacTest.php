<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

/**
 * Roles & Permissions integration tests.
 * Tests against the live seeded database.
 */
class RbacTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // DATABASE STATE
    // ─────────────────────────────────────────────────────────────────────

    public function test_exactly_5_roles_exist(): void
    {
        $roles = Role::pluck('name')->sort()->values()->all();
        $this->assertEquals(['Admin', 'Manager', 'Staff', 'SuperAdmin', 'Viewer'], $roles);
    }

    public function test_exactly_10_permissions_exist(): void
    {
        $this->assertSame(10, Permission::count());
    }

    public function test_all_permissions_use_hyphenated_names(): void
    {
        $bad = Permission::where('name', 'not like', '%-%')->pluck('name')->all();
        $this->assertEmpty($bad, 'Found non-hyphenated permissions: ' . implode(', ', $bad));
    }

    public function test_superadmin_has_all_permissions(): void
    {
        $role = Role::findByName('SuperAdmin');
        $this->assertSame(10, $role->permissions->count());
    }

    public function test_viewer_has_no_permissions(): void
    {
        $role = Role::findByName('Viewer');
        $this->assertSame(0, $role->permissions->count());
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — SUPERADMIN
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_access_branch_management(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
        $this->actingAs($user)->get('/branch')->assertStatus(200);
    }

    public function test_superadmin_can_access_user_management(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
        $this->actingAs($user)->get('/users')->assertStatus(200);
    }

    public function test_superadmin_can_access_roles(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
        $this->actingAs($user)->get('/roles')->assertStatus(200);
    }

    public function test_superadmin_can_access_permissions(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
        $this->actingAs($user)->get('/permissions')->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — ADMIN
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_can_access_user_management(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first();
        $this->actingAs($user)->get('/users')->assertStatus(200);
    }

    public function test_admin_cannot_access_branch_management(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'))
            ->first();
        $response = $this->actingAs($user)->get('/branch');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — STAFF
    // ─────────────────────────────────────────────────────────────────────

    public function test_staff_can_access_gr(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Staff'))
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['SuperAdmin', 'Admin']))
            ->first();
        $this->actingAs($user)->get('/gr')->assertStatus(200);
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Staff'))
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['SuperAdmin', 'Admin']))
            ->first();
        $response = $this->actingAs($user)->get('/users');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_staff_cannot_access_freight_memo(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Staff'))
            ->whereDoesntHave('roles', fn($q) => $q->whereIn('name', ['SuperAdmin', 'Admin', 'Manager']))
            ->first();
        $response = $this->actingAs($user)->get('/frieghtmemo');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — VIEWER
    // ─────────────────────────────────────────────────────────────────────

    public function test_viewer_can_access_gr_list(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'Viewer'))->first();
        if (!$user) $this->markTestSkipped('No Viewer user');
        $this->actingAs($user)->get('/gr')->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROLE PROTECTION
    // ─────────────────────────────────────────────────────────────────────

    public function test_cannot_delete_system_roles(): void
    {
        $user = User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
        $superAdminRole = Role::where('name', 'SuperAdmin')->first();

        $this->actingAs($user)
            ->delete(route('roles.destroy', $superAdminRole->id))
            ->assertRedirect(route('roles.index'));

        // Role should still exist
        $this->assertNotNull(Role::where('name', 'SuperAdmin')->first());
    }

    // ─────────────────────────────────────────────────────────────────────
    // USER CONTROLLER — ROLE ASSIGNMENT RESTRICTIONS
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_cannot_assign_superadmin_role(): void
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'))
            ->first();

        $superAdminRole = Role::where('name', 'SuperAdmin')->first();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Test Escalation',
            'email' => 'escalation_test_' . time() . '@test.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => $admin->office,
            'roles' => [$superAdminRole->id],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_admin_can_assign_staff_role(): void
    {
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))->first();
        $staffRole = Role::where('name', 'Staff')->first();

        $email = 'staff_test_' . time() . '@test.com';

        $this->actingAs($admin)->post('/users', [
            'name' => 'Test Staff User',
            'email' => $email,
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => $admin->office,
            'roles' => [$staffRole->id],
        ])->assertRedirect(route('users.index'));

        $this->assertNotNull(User::where('email', $email)->first());

        // Cleanup
        User::where('email', $email)->delete();
    }

    // ─────────────────────────────────────────────────────────────────────
    // DATA ISOLATION
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_only_sees_own_branch_users(): void
    {
        // Pick an admin from a non-Rajkot office
        $admin = User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->where('office', '!=', 'Rajkot')
            ->first();

        if (!$admin) {
            $this->markTestSkipped('No non-Rajkot Admin user found');
        }

        $response = $this->actingAs($admin)->get('/users');
        $response->assertStatus(200);
        // Should NOT see users from other offices (e.g. Rajkot SuperAdmin)
        $response->assertDontSee('admin@sxpress.test');
    }
}
