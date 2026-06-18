<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Branch;
use App\Models\Gr;

class BranchManagementTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function admin(): User
    {
        return User::where('role', 'branch_manager')->first();
    }

    // ─────────────────────────────────────────────────────────────────
    // ACCESS CONTROL
    // ─────────────────────────────────────────────────────────────────

    public function test_superadmin_can_access_branch_list(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('branch.index'))
            ->assertStatus(200)
            ->assertSee('Branch Management');
    }

    public function test_admin_cannot_access_branch_routes(): void
    {
        $admin = $this->admin();
        $response = $this->actingAs($admin)->get(route('branch.index'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    public function test_staff_cannot_access_branch_routes(): void
    {
        $staff = User::where('role', 'agent')->first();
        $response = $this->actingAs($staff)->get(route('branch.index'));
        $this->assertContains($response->getStatusCode(), [403, 302]);
    }

    // ─────────────────────────────────────────────────────────────────
    // CREATE BRANCH
    // ─────────────────────────────────────────────────────────────────

    public function test_create_form_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('branch.create'))
            ->assertStatus(200)
            ->assertSee('Add New Branch');
    }

    public function test_can_create_branch(): void
    {
        $this->actingAs($this->superAdmin())->post(route('branch.store'), [
            'branch_name' => 'Test Branch ' . time(),
            'branch_code' => 'TB' . time() % 1000,
            'gr_prefix' => 'ZZ',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'pincode' => '400001',
            'phone' => '+912212345678',
            'status' => 1,
        ])->assertRedirect('/branch');

        $branch = Branch::where('gr_prefix', 'ZZ')->first();
        $this->assertNotNull($branch);
        $this->assertSame('Mumbai', $branch->city);
        // Legacy columns should be synced
        $this->assertSame($branch->branch_name, $branch->name);
        $this->assertSame($branch->branch_code, $branch->code);

        // Cleanup
        $branch->delete();
    }

    public function test_create_validates_unique_gr_prefix(): void
    {
        $existing = Branch::first();

        $this->actingAs($this->superAdmin())->post(route('branch.store'), [
            'branch_name' => 'Duplicate Prefix Test',
            'branch_code' => 'DPT',
            'gr_prefix' => $existing->gr_prefix, // already used
            'city' => 'Test',
        ])->assertSessionHasErrors('gr_prefix');
    }

    public function test_create_validates_gr_prefix_format(): void
    {
        $this->actingAs($this->superAdmin())->post(route('branch.store'), [
            'branch_name' => 'Bad Prefix',
            'branch_code' => 'BPX',
            'gr_prefix' => '1A', // invalid — must be 2 uppercase letters
        ])->assertSessionHasErrors('gr_prefix');
    }

    public function test_create_validates_pincode_format(): void
    {
        $this->actingAs($this->superAdmin())->post(route('branch.store'), [
            'branch_name' => 'Pincode Test',
            'branch_code' => 'PCT',
            'gr_prefix' => 'PT',
            'pincode' => '12345', // only 5 digits
        ])->assertSessionHasErrors('pincode');
    }

    // ─────────────────────────────────────────────────────────────────
    // EDIT BRANCH
    // ─────────────────────────────────────────────────────────────────

    public function test_edit_form_loads(): void
    {
        $branch = Branch::first();
        $this->actingAs($this->superAdmin())
            ->get(route('branch.edit', $branch->id))
            ->assertStatus(200)
            ->assertSee($branch->branch_name);
    }

    public function test_can_update_branch_details(): void
    {
        // Create a test branch to update
        $branch = Branch::create([
            'branch_name' => 'Update Test',
            'branch_code' => 'UPT',
            'gr_prefix' => 'UP',
            'name' => 'Update Test',
            'code' => 'UPT',
            'city' => 'OldCity',
            'status' => 1,
            'is_active' => 1,
        ]);

        $this->actingAs($this->superAdmin())->put(route('branch.update', $branch->id), [
            'branch_name' => 'Update Test',
            'branch_code' => 'UPT',
            'gr_prefix' => 'UP',
            'city' => 'NewCity',
            'state' => 'Gujarat',
            'status' => 1,
        ])->assertRedirect('/branch');

        $branch->refresh();
        $this->assertSame('NewCity', $branch->city);

        // Cleanup
        $branch->delete();
    }

    public function test_gr_prefix_locked_when_grs_exist(): void
    {
        // Use Rajkot branch which has the gr_prefix AA and may have GRs
        $branch = Branch::where('branch_name', 'Rajkot - PN')->first();
        $hasGrs = Gr::where('office', $branch->branch_name)->exists();

        if (!$hasGrs) {
            // Create a dummy GR to trigger the lock
            Gr::create([
                'gr_no' => 'TEST-LOCK-001',
                'office' => $branch->branch_name,
                'from_dest' => $branch->branch_name,
                'to_dest' => 'Navagam',
                'copy_date' => now(),
                'consignor' => 'Test',
                'consignor_address' => 'Test',
                'consignee' => 'Test',
                'consignee_address' => 'Test',
                'nugs' => 1,
                'meth' => 'Bag',
                'description' => 'Test',
                'pm' => 'test',
                'eway_bill_number' => '123456789012',
                'weight' => 10,
                'frieght_amount' => 100,
                'total_amount' => 100,
            ]);
        }

        $response = $this->actingAs($this->superAdmin())->put(route('branch.update', $branch->id), [
            'branch_name' => $branch->branch_name,
            'branch_code' => $branch->branch_code,
            'gr_prefix' => 'XX', // try to change from AA to XX
            'status' => 1,
        ]);

        $response->assertSessionHasErrors('gr_prefix');
        $branch->refresh();
        $this->assertNotSame('XX', $branch->gr_prefix); // should still be the old value

        // Cleanup test GR if we created one
        Gr::where('gr_no', 'TEST-LOCK-001')->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // DELETE BRANCH
    // ─────────────────────────────────────────────────────────────────

    public function test_can_delete_empty_branch(): void
    {
        $branch = Branch::create([
            'branch_name' => 'Delete Me',
            'branch_code' => 'DEL',
            'gr_prefix' => 'DL',
            'name' => 'Delete Me',
            'code' => 'DEL',
            'status' => 1,
            'is_active' => 1,
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('branch.destroy', $branch->id))
            ->assertRedirect('/branch');

        $this->assertNull(Branch::find($branch->id));
    }

    public function test_cannot_delete_branch_with_users(): void
    {
        // Rajkot has users assigned
        $branch = Branch::where('branch_name', 'Rajkot - PN')->first();
        $this->assertTrue(User::where('office', 'Rajkot - PN')->exists());

        $this->actingAs($this->superAdmin())
            ->delete(route('branch.destroy', $branch->id))
            ->assertSessionHasErrors();

        // Branch should still exist
        $this->assertNotNull(Branch::find($branch->id));
    }

    public function test_cannot_delete_branch_with_grs(): void
    {
        // Create a branch with a GR (bypass observers to avoid activity_logs dependency)
        $branch = Branch::create([
            'branch_name' => 'GR Branch',
            'branch_code' => 'GRB',
            'gr_prefix' => 'GB',
            'name' => 'GR Branch',
            'code' => 'GRB',
            'status' => 1,
            'is_active' => 1,
        ]);

        // Insert directly to bypass any model observers
        \Illuminate\Support\Facades\DB::table('grs')->insert([
            'gr_no' => 'GB-00001',
            'office' => 'GR Branch',
            'from_dest' => 'GR Branch',
            'to_dest' => 'Rajkot - PN',
            'copy_date' => now(),
            'consignor' => 'Test',
            'consignor_address' => 'Test',
            'consignee' => 'Test',
            'consignee_address' => 'Test',
            'nugs' => 1,
            'meth' => 'Bag',
            'description' => 'Test',
            'pm' => 'test',
            'eway_bill_number' => '123456789012',
            'weight' => 10,
            'frieght_amount' => 100,
            'total_amount' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())
            ->delete(route('branch.destroy', $branch->id))
            ->assertSessionHasErrors();

        $this->assertNotNull(Branch::find($branch->id));

        // Cleanup
        \Illuminate\Support\Facades\DB::table('grs')->where('gr_no', 'GB-00001')->delete();
        $branch->delete();
    }

    // ─────────────────────────────────────────────────────────────────
    // VIEW
    // ─────────────────────────────────────────────────────────────────

    public function test_branch_view_shows_details(): void
    {
        $branch = Branch::first();
        $this->actingAs($this->superAdmin())
            ->get(route('branch.show', $branch->id))
            ->assertStatus(200)
            ->assertSee($branch->branch_name)
            ->assertSee($branch->gr_prefix);
    }
}
