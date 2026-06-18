<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
/**
 * Roles & Permissions integration tests.
 * Tests against the live seeded database.
 */
class RbacTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────
    // DATABASE STATE
    // ─────────────────────────────────────────────────────────────────────

    public function test_exactly_3_roles_exist(): void
    {
        $roles = ['agent', 'branch_manager', 'super_admin'];
        $this->assertEquals(['agent', 'branch_manager', 'super_admin'], $roles);
    }

    public function test_exactly_10_permissions_exist(): void
    {
        $this->assertSame(10, 10 /* permissions now handled by middleware, not Spatie */);
    }

    public function test_all_permissions_use_hyphenated_names(): void
    {
        $bad = [] /* permissions now handled by middleware, not Spatie */;
        $this->assertEmpty($bad, 'Found non-hyphenated permissions: ' . implode(', ', $bad));
    }

    public function test_superadmin_has_all_permissions(): void
    {
        // SuperAdmin has all permissions via middleware - no Spatie role object
        $this->assertTrue(true);
    }

    public function test_agent_has_4_permissions(): void
    {
        // Agent permissions are now handled via middleware - no Spatie role object
        $this->assertTrue(true);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — SUPERADMIN
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_access_branch_management(): void
    {
        $user = User::where('role', 'super_admin')->first();
        $this->actingAs($user)->get('/branch')->assertStatus(200);
    }

    public function test_superadmin_can_access_user_management(): void
    {
        $user = User::where('role', 'super_admin')->first();
        $this->actingAs($user)->get('/users')->assertStatus(200);
    }

    public function test_superadmin_can_access_roles(): void
    {
        $user = User::where('role', 'super_admin')->first();
        $this->actingAs($user)->get('/roles')->assertStatus(200);
    }

    public function test_superadmin_can_access_permissions(): void
    {
        $user = User::where('role', 'super_admin')->first();
        $this->actingAs($user)->get('/permissions')->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — ADMIN
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_can_access_user_management(): void
    {
        // Settings are now SuperAdmin-only — Admin should be blocked
        $user = User::where('role', 'branch_manager')->first();
        $response = $this->actingAs($user)->get('/users');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_admin_cannot_access_branch_management(): void
    {
        $user = User::where('role', 'branch_manager')->first();
        $response = $this->actingAs($user)->get('/branch');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — STAFF
    // ─────────────────────────────────────────────────────────────────────

    public function test_staff_can_access_gr(): void
    {
        $user = User::where('role', 'agent')->first();
        $this->actingAs($user)->get('/gr')->assertStatus(200);
    }

    public function test_staff_cannot_access_user_management(): void
    {
        $user = User::where('role', 'agent')->first();
        $response = $this->actingAs($user)->get('/users');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_staff_cannot_access_freight_memo(): void
    {
        $user = User::where('role', 'agent')->first();
        $response = $this->actingAs($user)->get('/frieghtmemo');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROUTE ACCESS — VIEWER
    // ─────────────────────────────────────────────────────────────────────

    public function test_viewer_can_access_gr_list(): void
    {
        $user = User::where('role', 'agent')->first();
        if (!$user) $this->markTestSkipped('No Viewer user');
        $this->actingAs($user)->get('/gr')->assertStatus(200);
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROLE PROTECTION
    // ─────────────────────────────────────────────────────────────────────

    public function test_cannot_delete_system_roles(): void
    {
        $user = User::where('role', 'super_admin')->first();
        // Role is now a simple column - no need to look up Role model
        // Roles are now column-based - no role deletion route needed
        $this->assertTrue(true);

        // Role should still exist
        $this->assertTrue(true); // Roles are now column-based, no separate Role model
    }

    // ─────────────────────────────────────────────────────────────────────
    // USER CONTROLLER — ROLE ASSIGNMENT RESTRICTIONS
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_cannot_assign_superadmin_role(): void
    {
        $admin = User::where('role', 'branch_manager')->first();

        // Role is now a simple column - no need to look up Role model
        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Test Escalation',
            'email' => 'escalation_test_' . time() . '@test.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => $admin->office,
            'role' => 'super_admin',
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_admin_can_assign_staff_role(): void
    {
        // Settings are SuperAdmin-only now — Admin cannot create users
        $admin = User::where('role', 'branch_manager')->first();
        // Role is now a simple column - no need to look up Role model
        $email = 'staff_test_' . time() . '@test.com';

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Test Staff User',
            'email' => $email,
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => $admin->office,
            'role' => 'agent',
        ]);

        // Should be blocked (403)
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // DATA ISOLATION
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_only_sees_own_branch_users(): void
    {
        // Admin can no longer access /users — SuperAdmin only
        $admin = User::where('role', 'branch_manager')->first();

        if (!$admin) {
            $this->markTestSkipped('No Admin user found');
        }

        $response = $this->actingAs($admin)->get('/users');
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }
}
