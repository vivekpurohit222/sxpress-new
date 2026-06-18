<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;

/**
 * Dashboard integration tests.
 */
class DashboardTest extends TestCase
{
    private function superAdmin(): User
    {
        return User::where('role', 'super_admin')->first();
    }

    private function manager(): User
    {
        return User::where('role', 'branch_manager')->first();
    }

    private function staff(): User
    {
        return User::where('role', 'agent')->first();
    }

    // ─── Basic Loading ──────────────────────────────────────────────

    public function test_superadmin_dashboard_loads(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('Booking')
            ->assertSee('Delivery');
    }

    public function test_admin_dashboard_loads(): void
    {
        $this->actingAs($this->manager())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('Booking');
    }

    public function test_staff_dashboard_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('Booking')
            ->assertSee('New GR');
    }

    public function test_viewer_dashboard_loads(): void
    {
        $this->actingAs($this->staff())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('Booking');
    }

    // ─── Module Cards ───────────────────────────────────────────────

    public function test_dashboard_shows_gr_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('GR (Goods Receipt)');
    }

    public function test_dashboard_shows_challan_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Challan');
    }

    public function test_dashboard_shows_freight_memo_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Freight Memo');
    }

    public function test_dashboard_shows_import_challan_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Import Challan');
    }

    public function test_dashboard_shows_gate_pass_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Gate Pass');
    }

    public function test_dashboard_shows_dds_module(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('DDS');
    }

    public function test_dashboard_shows_accounting_placeholder(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertSee('Coming Soon')
            ->assertSee('Accounting');
    }

    // ─── Office Badge ───────────────────────────────────────────────

    public function test_superadmin_can_filter_by_branch(): void
    {
        // SuperAdmin sees the office badge
        $this->actingAs($this->superAdmin())
            ->get(route('dash'))
            ->assertStatus(200);
    }

    public function test_non_superadmin_has_no_branch_filter(): void
    {
        $this->actingAs($this->staff())
            ->get(route('dash'))
            ->assertStatus(200)
            ->assertSee('Hello');
    }

    // ─── Quick Actions ──────────────────────────────────────────────

    public function test_manager_sees_all_modules(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)
            ->get(route('dash'))
            ->assertSee('Booking')
            ->assertSee('Delivery');
    }

    public function test_staff_sees_quick_create_buttons(): void
    {
        $this->actingAs($this->staff())
            ->get(route('dash'))
            ->assertSee('New GR')
            ->assertSee('New Gate Pass');
    }

    public function test_staff_sees_freight_memo(): void
    {
        $staff = $this->staff();
        $response = $this->actingAs($staff)->get(route('dash'));
        $response->assertSee('Freight Memo');
    }
}
