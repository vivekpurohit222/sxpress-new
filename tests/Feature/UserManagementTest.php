<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * User Management module integration tests.
 * Tests against the live seeded database.
 */
class UserManagementTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'SuperAdmin'))->first();
    }

    private function admin(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('name', 'Admin'))
            ->whereDoesntHave('roles', fn($q) => $q->where('name', 'SuperAdmin'))
            ->first();
    }

    // ─────────────────────────────────────────────────────────────────────
    // CREATE USER
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_access_create_form(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('users.create'))
            ->assertStatus(200)
            ->assertSee('Add New User');
    }

    public function test_superadmin_can_create_user(): void
    {
        $staffRole = Role::where('name', 'Staff')->first();
        $email = 'test_create_' . time() . '@test.com';

        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Test New User',
            'email' => $email,
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => 'Rajkot',
            'phone' => '+919876543210',
            'roles' => [$staffRole->id],
            'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', $email)->first();
        $this->assertNotNull($user);
        $this->assertSame('Rajkot', $user->office);
        $this->assertSame('+919876543210', $user->phone);
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->hasRole('Staff'));
        // branch_id should be resolved
        $this->assertNotNull($user->branch_id);

        // Cleanup
        $user->forceDelete();
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAs($this->superAdmin())->post(route('users.store'), [])
            ->assertSessionHasErrors(['name', 'email', 'password', 'office', 'roles']);
    }

    public function test_create_enforces_password_complexity(): void
    {
        $staffRole = Role::where('name', 'Staff')->first();

        // No uppercase
        $this->actingAs($this->superAdmin())->post(route('users.store'), [
            'name' => 'Test',
            'email' => 'weakpw@test.com',
            'password' => 'password1',
            'password_confirmation' => 'password1',
            'office' => 'Rajkot',
            'roles' => [$staffRole->id],
        ])->assertSessionHasErrors('password');
    }

    public function test_admin_cannot_create_user_for_other_branch(): void
    {
        $admin = $this->admin();
        $staffRole = Role::where('name', 'Staff')->first();
        $otherOffice = DB::table('branches')->where('name', '!=', $admin->office)->value('name');

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Cross Branch',
            'email' => 'crossbranch' . time() . '@test.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'office' => $otherOffice,
            'roles' => [$staffRole->id],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    // ─────────────────────────────────────────────────────────────────────
    // EDIT USER
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_edit_any_user(): void
    {
        $user = User::where('email', '!=', 'admin@sxpress.test')->first();

        $this->actingAs($this->superAdmin())
            ->get(route('users.edit', $user->id))
            ->assertStatus(200)
            ->assertSee('Edit ' . $user->name);
    }

    public function test_superadmin_can_update_user_name(): void
    {
        $user = User::where('email', 'operator1@sxpress.test')->first();
        $originalName = $user->name;
        $staffRole = Role::where('name', 'Staff')->first();

        $this->actingAs($this->superAdmin())->put(route('users.update', $user->id), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'office' => $user->office,
            'roles' => [$staffRole->id],
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);

        // Restore
        $user->update(['name' => $originalName]);
    }

    public function test_admin_cannot_edit_other_branch_user(): void
    {
        $admin = $this->admin();
        $otherBranchUser = User::where('office', '!=', $admin->office)->first();

        if (!$otherBranchUser) {
            $this->markTestSkipped('No users in other branches');
        }

        $response = $this->actingAs($admin)->get(route('users.edit', $otherBranchUser->id));
        $this->assertSame(403, $response->getStatusCode());
    }

    // ─────────────────────────────────────────────────────────────────────
    // DEACTIVATE / REACTIVATE
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_deactivate_user(): void
    {
        $user = User::where('email', 'operator2@sxpress.test')->first();
        $this->assertTrue($user->is_active);

        $this->actingAs($this->superAdmin())
            ->patch(route('users.toggle-active', $user->id))
            ->assertRedirect();

        $user->refresh();
        $this->assertFalse($user->is_active);

        // Reactivate to restore state
        $this->actingAs($this->superAdmin())
            ->patch(route('users.toggle-active', $user->id));
        $user->refresh();
        $this->assertTrue($user->is_active);
    }

    public function test_reactivate_user(): void
    {
        // Find an inactive user or make one inactive temporarily
        $user = User::where('email', 'inactive@sxpress.com')->first();
        if (!$user) {
            $this->markTestSkipped('No inactive user to test');
        }

        $this->assertFalse($user->is_active);

        $this->actingAs($this->superAdmin())
            ->patch(route('users.toggle-active', $user->id))
            ->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->is_active);

        // Restore inactive state
        $user->update(['is_active' => false]);
    }

    public function test_cannot_deactivate_yourself(): void
    {
        $sa = $this->superAdmin();

        $this->actingAs($sa)
            ->patch(route('users.toggle-active', $sa->id))
            ->assertSessionHasErrors();
    }

    public function test_admin_cannot_deactivate_other_branch_user(): void
    {
        $admin = $this->admin();
        $otherUser = User::where('office', '!=', $admin->office)->first();

        if (!$otherUser) {
            $this->markTestSkipped('No users in other branches');
        }

        $response = $this->actingAs($admin)
            ->patch(route('users.toggle-active', $otherUser->id));
        $this->assertSame(403, $response->getStatusCode());
    }

    // ─────────────────────────────────────────────────────────────────────
    // ROLE ASSIGNMENT
    // ─────────────────────────────────────────────────────────────────────

    public function test_admin_cannot_assign_superadmin_role(): void
    {
        $admin = $this->admin();
        $user = User::where('office', $admin->office)
            ->whereHas('roles', fn($q) => $q->where('name', 'Staff'))
            ->first();

        if (!$user) {
            $this->markTestSkipped('No Staff user in admin branch');
        }

        $superAdminRole = Role::where('name', 'SuperAdmin')->first();

        $response = $this->actingAs($admin)->put(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'office' => $user->office,
            'roles' => [$superAdminRole->id],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_superadmin_can_assign_any_role(): void
    {
        $user = User::where('email', 'operator3@sxpress.test')->first();
        $managerRole = Role::where('name', 'Manager')->first();

        $this->actingAs($this->superAdmin())->put(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'office' => $user->office,
            'roles' => [$managerRole->id],
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertTrue($user->hasRole('Manager'));

        // Restore
        $staffRole = Role::where('name', 'Staff')->first();
        $user->syncRoles([$staffRole]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // OFFICE ASSIGNMENT
    // ─────────────────────────────────────────────────────────────────────

    public function test_superadmin_can_change_user_office(): void
    {
        $user = User::where('email', 'operator4@sxpress.test')->first();
        $staffRole = Role::where('name', 'Staff')->first();
        $originalOffice = $user->office;
        $originalBranchId = $user->branch_id;

        $this->actingAs($this->superAdmin())->put(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'office' => 'Navagam',
            'roles' => [$staffRole->id],
        ])->assertRedirect(route('users.index'));

        $user->refresh();
        $this->assertSame('Navagam', $user->office);
        // branch_id should be updated too
        $navagamBranchId = DB::table('branches')->where('name', 'Navagam')->value('id');
        $this->assertEquals($navagamBranchId, $user->branch_id);

        // Restore
        $user->update(['office' => $originalOffice, 'branch_id' => $originalBranchId]);
    }

    public function test_admin_cannot_transfer_user_to_other_branch(): void
    {
        $admin = $this->admin();
        $user = User::where('office', $admin->office)
            ->whereHas('roles', fn($q) => $q->where('name', 'Staff'))
            ->first();

        if (!$user) {
            $this->markTestSkipped('No staff in admin branch');
        }

        $otherOffice = DB::table('branches')->where('name', '!=', $admin->office)->value('name');
        $staffRole = Role::where('name', 'Staff')->first();

        $response = $this->actingAs($admin)->put(route('users.update', $user->id), [
            'name' => $user->name,
            'email' => $user->email,
            'office' => $otherOffice,
            'roles' => [$staffRole->id],
        ]);

        $this->assertSame(403, $response->getStatusCode());
    }

    // ─────────────────────────────────────────────────────────────────────
    // USER LIST FILTERING
    // ─────────────────────────────────────────────────────────────────────

    public function test_user_list_shows_status_badge(): void
    {
        $response = $this->actingAs($this->superAdmin())->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('badge-success'); // at least one active user
    }

    public function test_status_filter_works(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('users.index', ['status' => 'inactive']));
        $response->assertStatus(200);
        $response->assertSee('badge-danger'); // inactive badge
    }

    // ─────────────────────────────────────────────────────────────────────
    // DELETE (soft-delete)
    // ─────────────────────────────────────────────────────────────────────

    public function test_delete_soft_deletes_user(): void
    {
        // Create a user to delete
        $staffRole = Role::where('name', 'Staff')->first();
        $email = 'deleteme_' . time() . '@test.com';
        $user = User::create([
            'name' => 'Delete Me',
            'email' => $email,
            'password' => 'Password1',
            'office' => 'Rajkot',
            'is_active' => true,
            'branch_id' => DB::table('branches')->where('name', 'Rajkot')->value('id'),
        ]);
        $user->assignRole($staffRole);

        $this->actingAs($this->superAdmin())
            ->delete(route('users.destroy', $user->id))
            ->assertRedirect(route('users.index'));

        // Hard query (bypassing soft-delete scope)
        $this->assertNotNull(User::withTrashed()->find($user->id));
        $this->assertNotNull(User::withTrashed()->find($user->id)->deleted_at);
        // Regular query should not find it
        $this->assertNull(User::find($user->id));

        // Cleanup
        $user->forceDelete();
    }

    public function test_cannot_delete_last_superadmin(): void
    {
        // Create a dedicated SuperAdmin for this test
        $testSa = User::create([
            'name' => 'Sole SuperAdmin',
            'email' => 'sole_sa_' . time() . '@test.com',
            'password' => 'Password1',
            'office' => 'Rajkot',
            'is_active' => true,
            'branch_id' => DB::table('branches')->where('name', 'Rajkot')->value('id'),
        ]);
        $testSa->assignRole('SuperAdmin');

        // Remove SuperAdmin role from all OTHER users temporarily
        $otherSAs = User::role('SuperAdmin')->where('id', '!=', $testSa->id)->get();
        foreach ($otherSAs as $other) {
            $other->removeRole('SuperAdmin');
        }

        // Now there's exactly 1 active SuperAdmin
        $this->assertSame(1, User::role('SuperAdmin')->where('is_active', true)->count());

        // Trying to delete the last SuperAdmin should fail
        $this->actingAs($testSa)
            ->delete(route('users.destroy', $testSa->id))
            ->assertSessionHasErrors();

        // User should still exist
        $this->assertNotNull(User::find($testSa->id));

        // Restore roles
        foreach ($otherSAs as $other) {
            $other->assignRole('SuperAdmin');
        }

        // Cleanup
        $testSa->removeRole('SuperAdmin');
        $testSa->forceDelete();
    }
}
